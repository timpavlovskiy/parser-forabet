package loader

import (
	"context"
	"loader_fonbet_go/loader/event_statistic"
	"log"
	"net/http"
	"sort"
	"strings"
	"sync"
	"time"
)

const eventIdTemplate = "[[eventId]]"

type eventAndTime struct {
	Time time.Time
	Id   string
}

type Loader struct {
	BootDriver          BootDriver
	httpHeaders         map[string]string
	templateUrlEvent    string
	dbEventMutex        sync.Mutex
	dbEventsStatistic   map[string]time.Time
	Logger              *log.Logger
	eventUpdateInterval time.Duration
	EventStatistic      EventStatistic
}

func NewLoader(bootDriver BootDriver, httpHeaders map[string]string, templateUrlEvent string, updateEventDuration int) *Loader {
	headers := normaliseHeaders(httpHeaders)

	if updateEventDuration == 0 {
		updateEventDuration = 200
	}

	return &Loader{
		BootDriver:          bootDriver,
		httpHeaders:         headers,
		templateUrlEvent:    templateUrlEvent,
		dbEventsStatistic:   make(map[string]time.Time, 50),
		Logger:              log.Default(),
		eventUpdateInterval: time.Duration(updateEventDuration) * time.Millisecond,
		EventStatistic:      &event_statistic.EmptyEventStatistic{},
	}
}

func (loader *Loader) SetDbEventIds(dbEventsId []string) {

	loader.dbEventMutex.Lock()
	newMap := make(map[string]string, len(dbEventsId))
	for _, id := range dbEventsId {
		newMap[id] = id
		if _, ok := loader.dbEventsStatistic[id]; !ok {
			loader.dbEventsStatistic[id] = time.Time{}
		}
	}
	for id, _ := range loader.dbEventsStatistic {
		if _, ok := newMap[id]; !ok {
			delete(loader.dbEventsStatistic, id)
		}
	}
	loader.dbEventMutex.Unlock()
	loader.EventStatistic.SetDbEventIds(dbEventsId)
}

func (loader *Loader) LoadEvents(ctx context.Context, handler func(eventId string, body []byte), waitLoadEvents *sync.WaitGroup) {

	loader.BootDriver.Start()
	requestChannel := loader.BootDriver.GetRequestChannel()
	responseChannel := loader.BootDriver.GetResponseChannel()

	waitReading := sync.WaitGroup{}
	waitReading.Add(1)
	go func() {
		loader.readResponse(responseChannel, handler)
		waitReading.Done()
	}()

	waitWriting := sync.WaitGroup{}
	waitWriting.Add(1)
	go func() {
	loop:
		for {
			loopWaiting, _ := context.WithDeadline(context.Background(), time.Now().Add(100*time.Millisecond))

			eventsForUpdating := loader.getEventsForUpdating()

			for _, event := range eventsForUpdating {
				if loader.eventIsNeedToUpdate(event) {
					requestMessage, err := loader.createRequestMessageByEventId(event.Id)
					if err != nil {
						loader.Logger.Printf("Ошибка создания запроса на событие: %s %v", event.Id, err)
						continue
					}
					requestChannel <- requestMessage
				}

				select {
				case <-ctx.Done():
					break loop
				default:
				}
			}
			<-loopWaiting.Done()
			select {
			case <-ctx.Done():
				break loop
			default:
			}
		}
		waitWriting.Done()
	}()
	waitWriting.Wait()
	loader.BootDriver.StopAndWait()
	waitReading.Wait()
	waitLoadEvents.Done()
}

func (loader *Loader) readResponse(responseChannel <-chan ResponseMessageDTO, handler func(eventId string, body []byte)) {
	for response := range responseChannel {
		if len(response.Body) > 0 {
			handler(response.Label, response.Body)
			loader.dbEventMutex.Lock()
			if _, ok := loader.dbEventsStatistic[response.Label]; ok {
				loader.dbEventsStatistic[response.Label] = response.TimeStart
			}
			loader.dbEventMutex.Unlock()
			loader.EventStatistic.Handler(response.Label, response.Body)
		}
	}
}

func (loader *Loader) getEventsForUpdating() []eventAndTime {
	loader.dbEventMutex.Lock()
	eventsForUpdating := make([]eventAndTime, 0, len(loader.dbEventsStatistic))
	for eventId, timeUpdate := range loader.dbEventsStatistic {
		eventsForUpdating = append(eventsForUpdating, eventAndTime{timeUpdate, eventId})
	}
	loader.dbEventMutex.Unlock()
	sort.Slice(eventsForUpdating, func(i, j int) bool {
		return eventsForUpdating[i].Time.Sub(eventsForUpdating[j].Time) > 0
	})
	return eventsForUpdating
}

func (loader *Loader) createRequestMessageByEventId(eventId string) (RequestMessageDTO, error) {
	message := RequestMessageDTO{Label: eventId, TimeStart: time.Now()}
	link := strings.Replace(loader.templateUrlEvent, eventIdTemplate, eventId, 1)
	httpRequest, err := http.NewRequest("GET", link, nil)
	if err != nil {
		return message, err
	}
	loader.addHeaders(httpRequest)
	message.Body = httpRequest
	return message, nil
}

func (loader *Loader) addHeaders(request *http.Request) {
	for key, value := range loader.httpHeaders {
		request.Header.Add(key, value)
	}
}

func (loader *Loader) eventIsNeedToUpdate(event eventAndTime) bool {
	duration := time.Now().Sub(event.Time)
	return duration > loader.eventUpdateInterval
}

func normaliseHeaders(headers map[string]string) map[string]string {
	out := map[string]string{}
	for headerName, value := range headers {
		if strings.ToLower(headerName) == "host" {
			continue
		}
		out[headerName] = value
	}
	return out
}

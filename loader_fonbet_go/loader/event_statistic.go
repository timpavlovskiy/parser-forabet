package loader

type EventStatistic interface {
	SetDbEventIds(dbEventIds []string)
	Handler(eventId string, body []byte)
}

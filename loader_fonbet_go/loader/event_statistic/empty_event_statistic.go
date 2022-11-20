package event_statistic

type EmptyEventStatistic struct {
}

func (s *EmptyEventStatistic) SetDbEventIds(dbEventIds []string) {

}
func (s *EmptyEventStatistic) Handler(eventId string, body []byte) {

}

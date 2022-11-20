package loader

type BootDriver interface {
	GetRequestChannel() chan<- RequestMessageDTO
	GetResponseChannel() <-chan ResponseMessageDTO
	Start()
	StopAndWait()
}

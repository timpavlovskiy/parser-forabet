package loader

import (
	"net/http"
	"time"
)

type RequestMessageDTO struct {
	TimeStart time.Time
	Label     string
	Body      *http.Request
}

type ResponseMessageDTO struct {
	TimeStart time.Time
	Label     string
	Body      []byte
}

package helpers

import (
	"compress/gzip"
	"io"
	"net/http"
)

func ReadHttpResponse(response *http.Response) ([]byte, error) {
	reader, err := GetHttpReaderByResponse(response)
	if err != nil {
		return []byte{}, err
	}
	bodyBytes, err := io.ReadAll(reader)
	if err != nil {
		return []byte{}, err
	}

	return bodyBytes, nil
}

func GetHttpReaderByResponse(response *http.Response) (io.ReadCloser, error) {
	switch response.Header.Get("Content-Encoding") {
	case "gzip":
		return gzip.NewReader(response.Body)
	default:
		return response.Body, nil
	}
}

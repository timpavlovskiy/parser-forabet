package helpers

import "encoding/json"

func JsonDecode[V any](str string) (V, error) {
	var out V
	err := json.Unmarshal([]byte(str), &out)
	return out, err
}

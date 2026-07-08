// custom_time.go : date/heure tolérante au décodage JSON (sérialise en RFC3339).

package models

import (
	"fmt"
	"strings"
	"time"
)

// CustomTime : time.Time acceptant plusieurs formats de date du front à la lecture JSON.
type CustomTime struct {
	time.Time
}

// UnmarshalJSON essaie plusieurs formats ; null/vide donne un time.Time zéro.
func (ct *CustomTime) UnmarshalJSON(b []byte) error {
	s := strings.Trim(string(b), "\"")
	if s == "null" || s == "" {
		ct.Time = time.Time{}
		return nil
	}
	formats := []string{
		time.RFC3339,
		"2006-01-02T15:04:05Z",
		"2006-01-02T15:04:05",
		"2006-01-02T15:04",
		"2006-01-02 15:04:05",
		"2006-01-02 15:04",
		"2006-01-02",
	}
	for _, f := range formats {
		if t, err := time.Parse(f, s); err == nil {
			ct.Time = t
			return nil
		}
	}
	return fmt.Errorf("invalid time format: %s", s)
}

// MarshalJSON sérialise en RFC3339, ou null si à zéro.
func (ct CustomTime) MarshalJSON() ([]byte, error) {
	if ct.Time.IsZero() {
		return []byte("null"), nil
	}
	return []byte(fmt.Sprintf("\"%s\"", ct.Time.Format(time.RFC3339))), nil
}

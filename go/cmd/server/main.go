package main

import (
	"context"
	"fmt"
	"log"
	"net/http"
	"os"
	"time"

	"github.com/jackc/pgx/v5/pgxpool"
)

func main() {
	dbUser := getEnv("DB_USER", "fiscal")
	dbPassword := getEnv("DB_PASSWORD", "fiscal")
	dbHost := getEnv("DB_HOST", "localhost")
	dbPort := getEnv("DB_PORT", "5432")
	dbName := getEnv("DB_NAME", "fiscal_go")

	postgresConnectionString := fmt.Sprintf(
		"postgres://%s:%s@%s:%s/%s?sslmode=disable",
		dbUser, dbPassword, dbHost, dbPort, dbName,
	)

	startupCtx, cancelStartup := context.WithTimeout(context.Background(), 15*time.Second)
	defer cancelStartup()

	dbPool, err := pgxpool.New(startupCtx, postgresConnectionString)
	if err != nil {
		log.Fatalf("db pool: %v", err)
	}
	defer dbPool.Close()

	if err := dbPool.Ping(startupCtx); err != nil {
		log.Fatalf("db ping: %v", err)
	}

	http.HandleFunc("/go", func(responseWriter http.ResponseWriter, request *http.Request) {
		pingCtx, cancelPing := context.WithTimeout(request.Context(), 2*time.Second)
		defer cancelPing()
		if err := dbPool.Ping(pingCtx); err != nil {
			http.Error(responseWriter, err.Error(), http.StatusServiceUnavailable)
			return
		}
		responseWriter.WriteHeader(http.StatusOK)
		_, _ = responseWriter.Write([]byte("ok"))
	})

	listenAddress := ":8080"
	if portFromEnv := os.Getenv("PORT"); portFromEnv != "" {
		listenAddress = ":" + portFromEnv
	}
	log.Printf("listening on %s", listenAddress)
	log.Fatal(http.ListenAndServe(listenAddress, nil))
}

func getEnv(key, fallback string) string {
	if value := os.Getenv(key); value != "" {
		return value
	}
	return fallback
}

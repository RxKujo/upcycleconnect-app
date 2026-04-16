# ============================================================
# Stage 1 — Build : compile le binaire Go
# ============================================================
FROM golang:1.25-alpine AS builder

WORKDIR /app

# Dépendances système pour la compilation
RUN apk add --no-cache git ca-certificates tzdata

# Télécharger les dépendances Go en premier (cache layer)
COPY go.mod go.sum ./
RUN go mod download

# Copier le source et compiler
COPY . .
RUN CGO_ENABLED=0 GOOS=linux GOARCH=amd64 \
    go build -a -installsuffix cgo \
    -ldflags="-w -s" \
    -o server ./cmd/server

# ============================================================
# Stage 2 — Runtime : image minimaliste (~10 MB)
# ============================================================
FROM scratch

WORKDIR /app

# Certificats SSL et timezone (copiés depuis builder)
COPY --from=builder /etc/ssl/certs/ca-certificates.crt /etc/ssl/certs/
COPY --from=builder /usr/share/zoneinfo /usr/share/zoneinfo

# Binaire compilé
COPY --from=builder /app/server .

EXPOSE 8888

ENTRYPOINT ["./server"]

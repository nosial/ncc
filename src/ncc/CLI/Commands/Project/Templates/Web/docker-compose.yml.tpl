version: '3.8'

services:
  app:
    build:
      context: .
    container_name: ${PACKAGE_NAME}
    image: ${PACKAGE_NAME}:${ASSEMBLY_VERSION}
    ports:
      - "8080:8080"
    restart: unless-stopped
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:8080/"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 40s
    networks:
      - app-network

networks:
  app-network:
    driver: bridge

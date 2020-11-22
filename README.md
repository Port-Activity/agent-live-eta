# Port Activity App / Live ETA integration

## Description
CLI job for polling timestamps from Live ETA service

Polling is done for MMSIs received from redis active port calls

## Configuring container
Copy .env.template to .env and fill values

## Configuring local development environment
Copy src/lib/init_local.php.sample.php to src/lib/init_local.php and fill values

## Running manually

### With docker compose
Configure container environment and
- `docker-compose build` Build container
- `docker-compose up` Start container. Will execute one polling run.
- `docker-compose stop` Stop container

### Locally
Configure development environment and
```make run```
FROM wordpress:cli-php8.3@sha256:a4fff017eb08c03187e90fe52d4bff8d3a4d4a5103de05829a6e747c4ca3056e
USER root
COPY docker/scheduler/run.sh /usr/local/bin/onestep-scheduler
USER 33:33
ENTRYPOINT ["onestep-scheduler"]

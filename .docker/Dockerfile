ARG BASE_CONTAINER
FROM ${BASE_CONTAINER}

ARG BASE_CONTAINER
ENV BASE_CONTAINER ${BASE_CONTAINER}

ARG APACHE_RUN_USER
ENV APACHE_RUN_USER ${APACHE_RUN_USER}

ARG APACHE_RUN_GROUP
ENV APACHE_RUN_GROUP ${APACHE_RUN_GROUP}

RUN apt update  \
    && apt install -y mc

RUN composer self-update --2

RUN php=$(echo "${BASE_CONTAINER}" | sed -nE 's/.*php-([0-9]+\.[0-9]+).*/\1/p') \
  && echo "xdebug.mode=develop,debug,coverage" >> /etc/php/${php}/mods-available/xdebug.ini

RUN sed -iE "s/^export APACHE_RUN_USER=.*$/export APACHE_RUN_USER=${APACHE_RUN_USER}/g" /etc/apache2/envvars \
    && sed -iE "s/^export APACHE_RUN_GROUP=.*$/export APACHE_RUN_GROUP=${APACHE_RUN_GROUP}/g" /etc/apache2/envvars

RUN phpenmod xdebug

RUN usermod -a -G 1000 www-data
RUN usermod -a -G 1000 mysql

FROM php:7.0-apache

RUN apt-get update && \
        apt-get install -y vim

COPY conf/ /etc/apache2/

RUN a2enmod lbmethod_byrequests
RUN a2enmod proxy_balancer
RUN a2enmod proxy proxy_http
RUN a2ensite 000-* 001*
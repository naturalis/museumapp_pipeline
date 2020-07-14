FROM php:7.2.1-apache
MAINTAINER maarten.schermer@naturalis.nl

RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# RUN apt-get update && apt-get install -y vim

COPY . /var/www/html/

RUN ln -s /data/squared_images/ squared_images
RUN ln -s /data/leenobject_images/ leenobject_images
RUN ln -s /data/stubs/ stubs
RUN ln -s /data/iucn_maps/ iucn_maps


FROM php:8.5 AS test

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN apt-get update \
    && apt-get install -y \
        python3 \
        python3-pip \
        nodejs \
        npm

WORKDIR /
COPY int int
COPY tester tester

WORKDIR /int
RUN composer install
WORKDIR /tester
RUN npm install -g
WORKDIR /tester/sol2xml
RUN pip3 install -r requirements.txt --break-system-packages

WORKDIR /tester
ENTRYPOINT [ "npm", "run", "start", "--" ]

FROM php:8.5 AS base

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY --from=node:24.12 /usr/local /usr/local

RUN apt-get update && apt-get install -y zip unzip python3 python3-pip

WORKDIR /
COPY int int
COPY tester tester

WORKDIR /int
RUN composer install --no-dev
WORKDIR /tester
RUN npm install -g --omit=dev
WORKDIR /tester/sol2xml
RUN pip3 install -r requirements.txt --break-system-packages

# ------------------------------------

FROM base AS check-base
ENTRYPOINT ["bash"]

# ------------------------------------

FROM base AS base-dev

WORKDIR /int
RUN composer install
WORKDIR /tester
RUN npm install -g

# ------------------------------------

FROM base-dev AS check-dev
ENTRYPOINT ["bash"]

# ------------------------------------

FROM base AS build-test
WORKDIR /tester
RUN npm run build

# ------------------------------------

FROM build-test AS runtime
ENTRYPOINT ["php", "/int/src/solint.php"]

# ------------------------------------

FROM runtime AS test
WORKDIR /tester
ENTRYPOINT ["node", "dist/tester.js"]

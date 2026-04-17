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
RUN npm install --omit=dev
WORKDIR /tester/sol2xml
RUN pip3 install -r requirements.txt --break-system-packages

# ------------------------------------

FROM base AS check

# Install all development dependencies (including code quality checkers)
WORKDIR /int
RUN composer install
WORKDIR /tester
RUN npm install

WORKDIR /
ENTRYPOINT ["bash"]

# ------------------------------------

FROM base AS build-test
WORKDIR /tester
RUN npm install
RUN npm run build

# ------------------------------------

FROM base AS runtime
WORKDIR /
COPY --from=build-test /tester/dist /tester/dist
WORKDIR /int
ENTRYPOINT ["php", "src/solint.php"]

# ------------------------------------

FROM runtime AS test
WORKDIR /tester
ENTRYPOINT ["node", "dist/tester.js"]

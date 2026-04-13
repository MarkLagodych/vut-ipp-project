FROM php:8.5 AS install-interpreter-only

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Required by PHP's Composer
RUN apt-get update && apt-get install -y zip unzip

WORKDIR /
COPY int int

WORKDIR /int
RUN composer install --no-dev

# ------------------------------------

FROM install-interpreter-only AS install-all

# It is much easier to install Python from APT due to lxml (needed by SOL2XML)
RUN apt-get update && apt-get install -y python3 python3-pip

# This is faster than APT
COPY --from=node:24.12 /usr/local /usr/local

WORKDIR /
COPY tester tester

WORKDIR /tester
RUN npm install -g --omit=dev
WORKDIR /tester/sol2xml
RUN pip3 install -r requirements.txt --break-system-packages

# ------------------------------------

FROM install-interpreter-only AS check-interpreter-only
ENTRYPOINT ["bash"]

# ------------------------------------

FROM install-all AS check-all
ENTRYPOINT ["bash"]

# ------------------------------------

FROM install-all AS install-dev

WORKDIR /int
RUN composer install
WORKDIR /tester
RUN npm install -g

# ------------------------------------

FROM install-dev AS check
ENTRYPOINT ["bash"]

# ------------------------------------

FROM install-interpreter-only AS runtime
ENTRYPOINT ["php", "/int/src/solint.php"]

# ------------------------------------

FROM install-all AS build-test
WORKDIR /tester
RUN npm run build

# ------------------------------------

FROM build-test AS test
WORKDIR /tester
ENTRYPOINT ["node", "dist/tester.js"]

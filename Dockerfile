FROM php:8.5 AS base

WORKDIR /

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY --from=node:24.12 /usr/local /usr/local

# Zip/Unzip are needed for PHP Composer
RUN apt-get update && apt-get install -y zip unzip python3 python3-pip

# Copy only the most necessary things
ADD /int /int/
COPY /int/composer.json /int/composer.lock /int/
COPY /tester/package.json /tester/package-lock.json /
COPY /tester/sol2xml/requirements.txt /
COPY /tester/sol2xml/*.py /tester/sol2xml/*.xsd /tester/sol2xml/

WORKDIR /int
RUN composer install --no-dev

# Installed dependencies to the root /node_modules/ directory
# will make them visible everywhere
WORKDIR /
RUN npm install --omit=dev

# Install Python dependencies globally
RUN pip3 install -r /requirements.txt --break-system-packages

# ------------------------------------

FROM base AS check


# Install all development dependencies (including code quality checkers)

# This installs into /int/vendor/
WORKDIR /int
RUN composer install

# This installs at the root /node_modules/, which is visible everywhere
WORKDIR /
RUN npm install

ENTRYPOINT ["bash"]

# ------------------------------------

FROM base AS build-test

WORKDIR /

ADD /tester/src /tester/src/
COPY /tester/tsconfig.json /tester/
COPY /tester/package.json /tester/
COPY /tester/package-lock.json /tester/

WORKDIR /tester

# Install development dependencies (e.g. TypeScript compiler)
RUN npm install
# Build in the dist/ directory
RUN npm run build

# The build artifacts are in /tester/dist

# ------------------------------------

FROM base AS runtime
WORKDIR /
COPY --from=build-test /tester/dist /tester/dist/
WORKDIR /int
ENTRYPOINT ["php", "src/solint.php"]

# ------------------------------------

FROM runtime AS test
WORKDIR /tester
ENTRYPOINT ["node", "dist/tester.js"]

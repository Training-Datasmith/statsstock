FROM php@sha256:6ce95208609dc66df163ab936c970b3b34cd901b85c747102c5999f08ade9143

RUN sed -i 's/deb.debian.org/archive.debian.org/g' /etc/apt/sources.list \
    && sed -i 's/security.debian.org/archive.debian.org/g' /etc/apt/sources.list \
    && sed -i '/stretch-updates/d' /etc/apt/sources.list \
    && echo 'Acquire::Check-Valid-Until "false";' > /etc/apt/apt.conf.d/99no-check-valid \
    && apt-get update \
    && (apt-get install -y --allow-unauthenticated --no-install-recommends default-libmysqlclient-dev \
        || apt-get install -y --allow-unauthenticated --no-install-recommends libmysqlclient-dev) \
    && docker-php-ext-install pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

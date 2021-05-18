FROM ubuntu:20.04 AS flyimg-builder

ENV DEBIAN_FRONTEND noninteractive

RUN apt-get update -y && apt-get -y --no-install-recommends install software-properties-common curl apt-transport-https ca-certificates gnupg && \
    add-apt-repository -y ppa:ondrej/php && \
    echo "deb [signed-by=/usr/share/keyrings/cloud.google.gpg] http://packages.cloud.google.com/apt cloud-sdk main" | tee -a /etc/apt/sources.list.d/google-cloud-sdk.list && curl https://packages.cloud.google.com/apt/doc/apt-key.gpg | apt-key --keyring /usr/share/keyrings/cloud.google.gpg  add - && \
    apt-get update -y && apt-get -y --no-install-recommends install \
    wget nginx zip unzip imagemagick webp libmagickwand-dev libyaml-dev \
    python3 python3-numpy libopencv-dev python3-setuptools opencv-data \
    gcc nasm build-essential make cmake wget vim git \
    php7.4-cli php7.4-fpm php7.4-gd php7.4-yaml php7.4-imagick php7.4-xdebug pkg-config php7.4-dev php7.4-xml php7.4-mbstring php7.4-pgsql php7.4-bcmath \
    google-cloud-sdk \
    ghostscript cron ffmpeg && \
    rm -rf /var/lib/apt/lists/*

RUN wget https://raw.githubusercontent.com/php-opencv/php-opencv-packages/master/opencv_4.5.0_amd64.deb && \
    dpkg -i opencv_4.5.0_amd64.deb && rm opencv_4.5.0_amd64.deb && \
    git clone https://github.com/php-opencv/php-opencv.git && \
    cd php-opencv && git checkout php7.4 && phpize && ./configure --with-php-config=/usr/bin/php-config && make && make install && \
    echo "extension=opencv.so" > /etc/php/7.4/cli/conf.d/opencv.ini && \
    echo "extension=opencv.so" > /etc/php/7.4/fpm/conf.d/opencv.ini && \
    rm -rf php-opencv && \
    wget "https://github.com/mozilla/mozjpeg/releases/download/v3.2/mozjpeg-3.2-release-source.tar.gz" && \
    tar xvf "mozjpeg-3.2-release-source.tar.gz" && \
    rm mozjpeg-3.2-release-source.tar.gz && \
    cd mozjpeg && \
    ./configure && \
    make && \
    make install && \
    cd .. && rm -rf mozjpeg && \
	cd /var && \
    curl https://bootstrap.pypa.io/get-pip.py -o get-pip.py && \
    python3 get-pip.py && \
    pip3 install numpy && \
    pip3 install opencv-python && \
    git clone https://github.com/flyimg/facedetect.git && \
    chmod +x /var/facedetect/facedetect && \
    ln -s /var/facedetect/facedetect /usr/local/bin/facedetect && \
    pip install git+https://github.com/flyimg/python-smart-crop && \
    pip3 install pillow && \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    cd /tmp/ && wget https://github.com/just-containers/s6-overlay/releases/download/v2.2.0.1/s6-overlay-amd64-installer && \
    chmod +x /tmp/s6-overlay-amd64-installer && /tmp/s6-overlay-amd64-installer / && \
    rm /tmp/s6-overlay-amd64-installer

RUN apt-get update -y && apt-get remove --purge -y zip libopencv-dev gcc nasm build-essential make cmake wget vim wget curl \
    libmagickwand-dev libyaml-dev software-properties-common pkg-config php7.4-dev vim git python3-setuptools && \
    apt autoremove --purge -y && \
    rm -rf /var/lib/apt/lists/*

FROM ubuntu:20.04
COPY --from=flyimg-builder / /

ENV PORT 80
WORKDIR /var/www/html

COPY . /var/www/html
RUN cp -fr /var/www/html/resources/etc/* /etc/ &&  \
    cp /etc/php-fpm.d/www.conf /etc/php/7.4/fpm/pool.d/ && \
    mkdir -p /run/php && \
    usermod -u 1000 -s /bin/bash www-data && \
    mkdir -p /var/www/html/var web/uploads/.tmb var/cache/ var/log/ && \
    chown -R www-data:www-data var/  web/uploads/ && \
    chmod 777 -R var/  web/uploads/

RUN composer update --no-dev --optimize-autoloader

# add cron to clear tmp dir
#daily at 1 am
RUN echo "0 1 * * * root find /var/www/html/var/tmp -type f -mtime +1 -delete" >> /etc/crontab
# enable cron service
RUN mkdir -p /etc/services.d/cron
RUN echo "#!/bin/sh \n /usr/sbin/cron -f" > /etc/services.d/cron/run && chmod +x /etc/services.d/cron/run

RUN cd /usr/share && ln -s opencv4 opencv

CMD ["/init"]
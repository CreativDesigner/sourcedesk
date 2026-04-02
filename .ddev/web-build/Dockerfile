#ddev-generated
ARG BASE_IMAGE
FROM $BASE_IMAGE

# Install wkhtmltopdf from bookworm arm64 package
RUN (apt-get update || true) && DEBIAN_FRONTEND=noninteractive apt-get install -y -o Dpkg::Options::="--force-confold" --no-install-recommends --no-install-suggests curl ca-certificates fontconfig libfontconfig1 fonts-freefont-ttf libx11-6 libxext6 libxrender1 libxtst6 libssl3 jq && \
    curl -L -o /tmp/wkhtmltox.deb https://github.com/wkhtmltopdf/packaging/releases/download/0.12.6.1-3/wkhtmltox_0.12.6.1-3.bookworm_arm64.deb && \
    ls -la /tmp/wkhtmltox.deb && \
    file /tmp/wkhtmltox.deb && \
    dpkg-deb -x /tmp/wkhtmltox.deb /tmp/wkhtmltox && \
    ls -la /tmp/wkhtmltox/usr/local/bin/ && \
    cp /tmp/wkhtmltox/usr/local/bin/wkhtmltopdf /usr/bin/ && \
    cp /tmp/wkhtmltox/usr/local/bin/wkhtmltoimage /usr/bin/ && \
    chmod +x /usr/bin/wkhtmltopdf /usr/bin/wkhtmltoimage && \
    rm -rf /tmp/wkhtmltox*
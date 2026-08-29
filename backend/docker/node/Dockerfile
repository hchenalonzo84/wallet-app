FROM node:26.8.1-bookworm-slim

ARG NPM_VERSION=12.0.2

RUN npm install --global "npm@${NPM_VERSION}" \
    && node --version \
    && npm --version

WORKDIR /var/www/html

EXPOSE 5173

CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
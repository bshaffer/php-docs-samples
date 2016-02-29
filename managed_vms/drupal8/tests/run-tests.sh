#!/usr/bin/env bash
set -e

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
SAMPLE_DIR="${DIR}/.."
DRUPAL_DIR="${SAMPLE_DIR}/drupal8.test"

VARS=(
    GCLOUD_PROJECT_ID
    GCLOUD_VERSION_ID
    DRUPAL_ADMIN_USERNAME
    DRUPAL_ADMIN_PASSWORD
    DRUPAL_DATABASE_NAME
    DRUPAL_DATABASE_USER
    DRUPAL_DATABASE_PASS
)

# Check for necessary envvars.
PREREQ="true"
for v in "${VARS[@]}"; do
    if [ -z "${!v}" ]; then
        echo "Please set ${v} envvar."
        PREREQ="false"
    fi
done

# Exit when any of the necessary envvar is not set.
if [ "${PREREQ}" = "false" ]; then
    exit 1
fi

# Install drupal
if [ ! -e ${HOME}/bin/drupal ]; then
    curl https://drupalconsole.com/installer -L -o drupal
    chmod +x drupal
    mv drupal "${HOME}/bin/drupal"
fi

# cleanup installation dir
rm -Rf $DRUPAL_DIR
INSTALL_FILE="${DIR}/config/install_drupal8.yml"

cp "${INSTALL_FILE}.dist" $INSTALL_FILE
sed -i -e "s/@@DRUPAL_DATABASE_NAME@@/${DRUPAL_DATABASE_NAME}/" $INSTALL_FILE
sed -i -e "s/@@DRUPAL_DATABASE_USER@@/${DRUPAL_DATABASE_USER}/" $INSTALL_FILE
sed -i -e "s/@@DRUPAL_DATABASE_PASS@@/${DRUPAL_DATABASE_PASS}/" $INSTALL_FILE
sed -i -e "s/@@DRUPAL_DATABASE_HOST@@/${DRUPAL_DATABASE_HOST}/" $INSTALL_FILE
sed -i -e "s/@@DRUPAL_ADMIN_USERNAME@@/${DRUPAL_ADMIN_USERNAME}/" $INSTALL_FILE
sed -i -e "s/@@DRUPAL_ADMIN_PASSWORD@@/${DRUPAL_ADMIN_PASSWORD}/" $INSTALL_FILE

drupal init --root=$DIR
drupal chain --file=$INSTALL_FILE

## Perform steps outlined in the README ##

# Copy configuration files to user home directory:
cp "${SAMPLE_DIR}/app.yaml" "${DRUPAL_DIR}/app.yaml"
cp "${SAMPLE_DIR}/nginx-app.conf" "${DRUPAL_DIR}/nginx-app.conf"
cp "${SAMPLE_DIR}/Dockerfile" "${DRUPAL_DIR}/Dockerfile"
cp "${SAMPLE_DIR}/php.ini" "${DRUPAL_DIR}/php.ini"

# Deploy to gcloud
gcloud preview app deploy \
  --no-promote --quiet --stop-previous-version \
  --project=${GCLOUD_PROJECT_ID} \
  --version=${GCLOUD_VERSION_ID}

# perform the test
curl -vf https://${GCLOUD_VERSION_ID}-dot-${GCLOUD_PROJECT_ID}.appspot.com

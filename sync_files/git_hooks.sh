#!/bin/sh
# Author: Diego Bastidas
# Description: copy git_hooks into modules

cd ../../web/modules/custom
proyect_foler=$(git rev-parse --show-toplevel)
GREEN=$(tput setaf 2)
NORMAL=$(tput sgr0)
RED=$(tput setaf 1)

for folder in $(ls); do
  echo "===================================="
  cd $folder
  if [ -d ".git" ]; then
    cp $proyect_foler/virtualization/sync_files/git_hooks/pre-commit .git/hooks
    echo "${GREEN} pre-commit hooks copied into $folder/.git/hooks ${NORMAL}"
  else
    echo "${RED} .git folder does not exist into $folder ${NORMAL}"
  fi
  cd ../
  echo "===================================="
done;
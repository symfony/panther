#!/usr/bin/env bash
# Author: Kévin Dunglas <dunglas@gmail.com>
# Download the last version of geckodriver binaries

cd "$(dirname "$0")"

latest=$(curl -s https://api.github.com/repos/mozilla/geckodriver/releases/latest | jq -r '.tag_name')

echo "Downloading geckodriver version ${latest}..."

echo ${latest} > version.txt

# Unix
declare -a binaries=("linux64" "macos")
for name in "${binaries[@]}"
do
   curl -Ls https://github.com/mozilla/geckodriver/releases/download/${latest}/geckodriver-${latest}-${name}.tar.gz | tar xz
   if [ -f "geckodriver" ]; then
      mv geckodriver geckodriver-${name}
   fi
done

# Windows
curl -Ls https://github.com/mozilla/geckodriver/releases/download/${latest}/geckodriver-${latest}-win32.zip -O
unzip -q -o geckodriver-${latest}-win32.zip
rm geckodriver-${latest}-win32.zip

echo "Done."

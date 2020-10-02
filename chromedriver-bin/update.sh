#!/usr/bin/env bash
# Author: Kévin Dunglas <dunglas@gmail.com>
# Download the last version of ChromeDriver binaries

cd "$(dirname "$0")"

latest=$(curl -s https://chromedriver.storage.googleapis.com/LATEST_RELEASE)

echo "Downloading ChromeDriver version ${latest}..."

declare -a binaries=("chromedriver_linux64" "chromedriver_mac64" "chromedriver_win32")
for name in "${binaries[@]}"
do
   curl -s https://chromedriver.storage.googleapis.com/${latest}/${name}.zip -O
   unzip -q -o ${name}.zip
   rm ${name}.zip
   if [[ -f "chromedriver" ]]; then
      mv chromedriver ${name}
   fi
done
curl -s https://chromedriver.storage.googleapis.com/${latest}/notes.txt -O
echo "Done."

#!/usr/bin/env bash
# Author: Kévin Dunglas <dunglas@gmail.com>
# Download the last version of ChromeDriver binaries

script_root=$(cd `dirname $0` && pwd -P)

latest=$(curl -s https://chromedriver.storage.googleapis.com/LATEST_RELEASE)

echo "Downloading ChromeDriver version ${latest}..."

declare -a binaries=("chromedriver_linux64" "chromedriver_mac64" "chromedriver_win32")
for name in "${binaries[@]}"
do
   curl -s https://chromedriver.storage.googleapis.com/${latest}/${name}.zip -O
   unzip -q -o ${name}.zip
   rm ${name}.zip
   if [[ -f "chromedriver" ]]; then
      mv chromedriver ${script_root}/${name}
   fi
   if [[ -f "chromedriver.exe" ]]; then
     mv chromedriver.exe ${script_root}/chromedriver.exe
   fi
done
curl -s https://chromedriver.storage.googleapis.com/${latest}/notes.txt --output ${script_root}/notes.txt
echo "Done."

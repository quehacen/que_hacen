# -*- coding: utf-8 -*-

# This Python script finds recursively all .csv
# files under the current directory that have
# been added to Git, then saves the
# resulting list to the file "readme.md",
# which GitHub uses as a read me file.

import os, os.path, subprocess, datetime

execstr = "git ls-files ./ | grep -E '.csv'"
result = subprocess.check_output(execstr, shell=True).split('\n')

absolutePath = 'https://github.com/hamoid/que_hacen/blob/master/csv'
now = datetime.datetime.now()

f = open('readme.md', "w")
f.write('**Estos archivos .csv se generaron automáticamente en %s**\n' % now.strftime("%Y-%m-%d %H:%M"))
f.write('**con datos descargados desde [congreso.es](http://www.congreso.es/portal/page/portal/Congreso/Congreso/Actualidad/Votaciones)**\n\n')

for line in result:
  if line:
    f.write('[%s](%s/%s)  \n' % (os.path.basename(line), absolutePath, line))
f.close()


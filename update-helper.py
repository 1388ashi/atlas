import subprocess
import os
from pprint import pprint
import shutil

currentPath = os.getcwd().replace("\\", "/")
from1 = subprocess.check_output(
    'git ls-files --other --modified --exclude-standard', shell=True).decode("utf-8")
from2 = subprocess.check_output(
    'git diff --name-only --staged', shell=True).decode("utf-8")

first = from1.split('\n')
second = from2.split('\n')
all = first + second
final = []
for item in all:
    exists = False
    for item2 in final:
        if item2 == item:
            exists = True
            break
    if item != '' and not exists:
        final.append(item)
pprint((final))

filesToMove = []
for item in final:
    print(item + ' ? (y/N)')
    userInput = ''
    userInput = input()
    if userInput == '' or userInput == "N" or userInput == "n":
        continue
    filesToMove.append(item)
pprint(filesToMove)


print("Enter previous version without v: ")
prevVersion = input()
print("Enter next version without v: ")
nextVersion = input()
dest = currentPath + "/vendor/shetabit/shopit/src/Update/updates/project/v" + prevVersion + "-v" + nextVersion
for item in filesToMove:
    if not os.path.isdir(os.path.join(dest, os.path.dirname(item))):
        print (os.path.join(dest,  os.path.dirname(item)).replace("\\\\", "\\"))
        os.makedirs(os.path.join(dest,  os.path.dirname(item)).replace("\\\\", "\\"), exist_ok=True)
    shutil.copy(currentPath + "/" + item, dest + "/" + os.path.dirname(item))

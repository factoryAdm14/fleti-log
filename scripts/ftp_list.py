#!/usr/bin/env python3
from __future__ import annotations
import ftplib, re, sys
from pathlib import Path
ROOT = Path(__file__).resolve().parents[1]
CREDS = ROOT / "DEPLOYMENT_CREDENTIALS.local.md"
text = CREDS.read_text()
section = text.split("### FTP", 1)[1].split("###", 1)[0]
user = re.search(r"- User: (.+)", section).group(1).strip()
password = re.search(r"- Password: (.+)", section).group(1).strip()
ip = re.search(r"- Host: (.+)", text.split("### SSH",1)[1]).group(1).strip()
ftp = ftplib.FTP(ip, timeout=60)
ftp.login(user, password)
print('PWD', ftp.pwd())
ftp.retrlines('LIST')
ftp.quit()

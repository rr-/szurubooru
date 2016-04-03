import smtplib
from email.mime.text import MIMEText

class Mailer(object):
    def __init__(self, config):
        self._config = config

    def send(self, sender, recipient, subject, body):
        msg = MIMEText(body)
        msg['Subject'] = subject
        msg['From'] = sender
        msg['To'] = recipient

        smtp = smtplib.SMTP(
            self._config['smtp']['host'],
            int(self._config['smtp']['port']))
        smtp.login(self._config['smtp']['user'], self._config['smtp']['pass'])
        smtp.send_message(msg)
        smtp.quit()

import smtplib
import email.mime.text

class Mailer(object):
    def __init__(self, config):
        self._config = config

    def send(self, sender, recipient, subject, body):
        msg = email.mime.text.MIMEText(body)
        msg['Subject'] = subject
        msg['From'] = sender
        msg['To'] = recipient

        smtp = smtplib.SMTP(
            self._config['smtp']['host'],
            int(self._config['smtp']['port']))
        smtp.login(self._config['smtp']['user'], self._config['smtp']['pass'])
        smtp.send_message(msg)
        smtp.quit()

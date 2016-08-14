import smtplib
import email.mime.text
from szurubooru import config


def send_mail(sender, recipient, subject, body):
    msg = email.mime.text.MIMEText(body)
    msg['Subject'] = subject
    msg['From'] = sender
    msg['To'] = recipient

    smtp = smtplib.SMTP(
        config.config['smtp']['host'], int(config.config['smtp']['port']))
    smtp.login(config.config['smtp']['user'], config.config['smtp']['pass'])
    smtp.send_message(msg)
    smtp.quit()

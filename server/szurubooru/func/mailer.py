import email.mime.text
import smtplib

from szurubooru import config


def send_mail(sender: str, recipient: str, subject: str, body: str) -> None:
    msg = email.mime.text.MIMEText(body)
    msg["Subject"] = subject
    msg["From"] = sender
    msg["To"] = recipient

    smtp = smtplib.SMTP(
        config.config["smtp"]["host"], int(config.config["smtp"]["port"])
    )
    try:
        smtp.starttls()
    except smtplib.SMTPNotSupportedError:
        pass
    smtp.login(config.config["smtp"]["user"], config.config["smtp"]["pass"])
    smtp.send_message(msg)
    smtp.quit()

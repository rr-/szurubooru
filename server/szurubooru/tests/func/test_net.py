from datetime import datetime
from unittest.mock import patch

import pytest

from szurubooru import errors
from szurubooru.func import net
from szurubooru.func.util import get_sha1


@pytest.fixture(autouse=True)
def inject_config(tmpdir, config_injector):
    config_injector(
        {
            "user_agent": None,
            "max_dl_filesize": 1.0e6,
            "data_dir": str(tmpdir.mkdir("data")),
        }
    )


def test_download():
    url = "http://info.cern.ch/hypertext/WWW/TheProject.html"

    expected_content = (
        b'<HEADER>\n<TITLE>The World Wide Web project</TITLE>\n<NEXTID N="'
        + b'55">\n</HEADER>\n<BODY>\n<H1>World Wide Web</H1>The WorldWideWeb'
        + b' (W3) is a wide-area<A\nNAME=0 HREF="WhatIs.html">\nhypermedia</'
        + b"A> information retrieval\ninitiative aiming to give universal\na"
        + b"ccess to a large universe of documents.<P>\nEverything there is "
        + b"online about\nW3 is linked directly or indirectly\nto this docum"
        + b'ent, including an <A\nNAME=24 HREF="Summary.html">executive\nsum'
        + b'mary</A> of the project, <A\nNAME=29 HREF="Administration/Mailin'
        + b'g/Overview.html">Mailing lists</A>\n, <A\nNAME=30 HREF="Policy.h'
        + b'tml">Policy</A> , November\'s  <A\nNAME=34 HREF="News/9211.html"'
        + b'>W3  news</A> ,\n<A\nNAME=41 HREF="FAQ/List.html">Frequently Ask'
        + b'ed Questions</A> .\n<DL>\n<DT><A\nNAME=44 HREF="../DataSources/T'
        + b"op.html\">What's out there?</A>\n<DD> Pointers to the\nworld's "
        + b'online information,<A\nNAME=45 HREF="../DataSources/bySubject/Ov'
        + b'erview.html"> subjects</A>\n, <A\nNAME=z54 HREF="../DataSources/'
        + b'WWW/Servers.html">W3 servers</A>, etc.\n<DT><A\nNAME=46 HREF="He'
        + b'lp.html">Help</A>\n<DD> on the browser you are using\n<DT><A\nNA'
        + b'ME=13 HREF="Status.html">Software Products</A>\n<DD> A list of W'
        + b"3 project\ncomponents and their current state.\n(e.g. <A\nNAME=2"
        + b'7 HREF="LineMode/Browser.html">Line Mode</A> ,X11 <A\nNAME=35 HR'
        + b'EF="Status.html#35">Viola</A> ,  <A\nNAME=26 HREF="NeXT/WorldWid'
        + b'eWeb.html">NeXTStep</A>\n, <A\nNAME=25 HREF="Daemon/Overview.htm'
        + b'l">Servers</A> , <A\nNAME=51 HREF="Tools/Overview.html">Tools</A'
        + b'> ,<A\nNAME=53 HREF="MailRobot/Overview.html"> Mail robot</A> ,<'
        + b'A\nNAME=52 HREF="Status.html#57">\nLibrary</A> )\n<DT><A\nNAME=4'
        + b'7 HREF="Technical.html">Technical</A>\n<DD> Details of protocols'
        + b', formats,\nprogram internals etc\n<DT><A\nNAME=40 HREF="Bibliog'
        + b'raphy.html">Bibliography</A>\n<DD> Paper documentation\non  W3 a'
        + b'nd references.\n<DT><A\nNAME=14 HREF="People.html">People</A>\n<'
        + b"DD> A list of some people involved\nin the project.\n<DT><A\nNAM"
        + b'E=15 HREF="History.html">History</A>\n<DD> A summary of the hist'
        + b'ory\nof the project.\n<DT><A\nNAME=37 HREF="Helping.html">How ca'
        + b"n I help</A> ?\n<DD> If you would like\nto support the web..\n<D"
        + b'T><A\nNAME=48 HREF="../README.html">Getting code</A>\n<DD> Getti'
        + b'ng the code by<A\nNAME=49 HREF="LineMode/Defaults/Distribution.h'
        + b'tml">\nanonymous FTP</A> , etc.</A>\n</DL>\n</BODY>\n'
    )

    actual_content = net.download(url)
    assert actual_content == expected_content


@pytest.mark.parametrize(
    "url",
    [
        "https://samples.ffmpeg.org/MPEG-4/video.mp4",
    ],
)
def test_too_large_download(url):
    pytest.xfail("Download limit not implemented yet")
    with pytest.raises(errors.ProcessingError):
        net.download(url)


@pytest.mark.parametrize(
    "url,expected_sha1",
    [
        (
            "https://www.youtube.com/watch?v=C0DPdy98e4c",
            "365af1c8f59c6865e1a84c6e13e3e25ff89e0ba1",
        ),
        (
            "https://gfycat.com/immaterialchillyiberianmole",
            "953000e81d7bd1da95ce264f872e7b6c4a6484be",
        ),
    ],
)
def test_video_download(url, expected_sha1):
    actual_content = net.download(url, use_video_downloader=True)
    assert get_sha1(actual_content) == expected_sha1


@pytest.mark.parametrize(
    "url",
    [
        "https://samples.ffmpeg.org/flac/short.flac",  # not a video
        "https://www.youtube.com/watch?v=dQw4w9WgXcQ",  # video too large
    ],
)
def test_failed_video_download(url):
    with pytest.raises(errors.ThirdPartyError):
        net.download(url, use_video_downloader=True)


def test_no_webhooks(config_injector):
    config_injector({"webhooks": []})
    res = net.post_to_webhooks(None)
    assert len(res) == 0


@pytest.mark.parametrize(
    "webhook,status_code",
    [
        ("https://postman-echo.com/post", 200),
        ("http://localhost/", 400),
        ("https://postman-echo.com/get", 400),
    ],
)
def test_single_webhook(config_injector, webhook, status_code):
    ret = net._post_to_webhook(webhook, {"test_arg": "test_value"})
    assert ret == status_code


def test_multiple_webhooks(config_injector):
    config_injector(
        {
            "webhooks": [
                "https://postman-echo.com/post",
                "https://postman-echo.com/get",
            ]
        }
    )
    threads = net.post_to_webhooks({"test_arg": "test_value"})
    assert len(threads) == 2


def test_malformed_webhooks(config_injector):
    with pytest.raises(ValueError):
        net._post_to_webhook("malformed_url", {"test_arg": "test_value"})

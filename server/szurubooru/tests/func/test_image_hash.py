import pytest
from numpy import array_equal

from szurubooru.func import image_hash


def test_signature_functions(read_asset, config_injector):
    sig1 = image_hash.generate_signature(read_asset("jpeg.jpg"))
    sig2 = image_hash.generate_signature(read_asset("jpeg-similar.jpg"))

    sig1_repacked = image_hash.unpack_signature(
        image_hash.pack_signature(sig1)
    )
    sig2_repacked = image_hash.unpack_signature(
        image_hash.pack_signature(sig2)
    )
    assert array_equal(sig1, sig1_repacked)
    assert array_equal(sig2, sig2_repacked)

    dist1 = image_hash.normalized_distance([sig1], sig2)
    assert abs(dist1[0] - 0.19713075553164386) < 1e-8

    dist2 = image_hash.normalized_distance([sig2], sig2)
    assert abs(dist2[0]) < 1e-8

    words1 = image_hash.generate_words(sig1)
    words2 = image_hash.generate_words(sig2)
    words_match = sum(word1 == word2 for word1, word2 in zip(words1, words2))
    assert words_match == 18


def test_signature_heif(read_asset, config_injector):
    sig1 = image_hash.generate_signature(read_asset("heif.heif"))
    sig2 = image_hash.generate_signature(read_asset("heif-similar.heif"))

    sig1_repacked = image_hash.unpack_signature(
        image_hash.pack_signature(sig1)
    )
    sig2_repacked = image_hash.unpack_signature(
        image_hash.pack_signature(sig2)
    )
    assert array_equal(sig1, sig1_repacked)
    assert array_equal(sig2, sig2_repacked)

    dist1 = image_hash.normalized_distance([sig1], sig2)
    assert abs(dist1[0] - 0.136777724290135) < 1e-8

    dist2 = image_hash.normalized_distance([sig2], sig2)
    assert abs(dist2[0]) < 1e-8

    words1 = image_hash.generate_words(sig1)
    words2 = image_hash.generate_words(sig2)
    words_match = sum(word1 == word2 for word1, word2 in zip(words1, words2))
    assert words_match == 43


def test_signature_avif(read_asset, config_injector):
    sig1 = image_hash.generate_signature(read_asset("avif.avif"))
    sig2 = image_hash.generate_signature(read_asset("avif-similar.avif"))

    sig1_repacked = image_hash.unpack_signature(
        image_hash.pack_signature(sig1)
    )
    sig2_repacked = image_hash.unpack_signature(
        image_hash.pack_signature(sig2)
    )
    assert array_equal(sig1, sig1_repacked)
    assert array_equal(sig2, sig2_repacked)

    dist1 = image_hash.normalized_distance([sig1], sig2)
    assert abs(dist1[0] - 0.22628712858355998) < 1e-8

    dist2 = image_hash.normalized_distance([sig2], sig2)
    assert abs(dist2[0]) < 1e-8

    words1 = image_hash.generate_words(sig1)
    words2 = image_hash.generate_words(sig2)
    words_match = sum(word1 == word2 for word1, word2 in zip(words1, words2))
    assert words_match == 12

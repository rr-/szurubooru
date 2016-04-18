import datetime
import os
import json
from szurubooru import config, db
from szurubooru.util import tags

def test_export(tmpdir, session, config_injector, tag_factory):
    config_injector({
        'data_dir': str(tmpdir)
    })
    sug1 = tag_factory(names=['sug1'])
    sug2 = tag_factory(names=['sug2'])
    imp1 = tag_factory(names=['imp1'])
    imp2 = tag_factory(names=['imp2'])
    tag = tag_factory(names=['alias1', 'alias2'])
    tag.post_count = 1
    session.add_all([tag, sug1, sug2, imp1, imp2])
    session.flush()
    session.add_all([
        db.TagSuggestion(tag.tag_id, sug1.tag_id),
        db.TagSuggestion(tag.tag_id, sug2.tag_id),
        db.TagImplication(tag.tag_id, imp1.tag_id),
        db.TagImplication(tag.tag_id, imp2.tag_id),
    ])
    session.flush()

    tags.export_to_json()
    export_path = os.path.join(config.config['data_dir'], 'tags.json')
    assert os.path.exists(export_path)
    with open(export_path, 'r') as handle:
        assert json.loads(handle.read()) == [
            {
                'names': ['alias1', 'alias2'],
                'usages': 1,
                'suggestions': ['sug1', 'sug2'],
                'implications': ['imp1', 'imp2'],
            },
            {'names': ['sug1'], 'usages': 0},
            {'names': ['sug2'], 'usages': 0},
            {'names': ['imp1'], 'usages': 0},
            {'names': ['imp2'], 'usages': 0},
        ]

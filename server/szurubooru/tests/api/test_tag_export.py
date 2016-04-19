import datetime
import os
import json
from szurubooru import config, db
from szurubooru.util import tags

def test_export(
        tmpdir,
        query_counter,
        session,
        config_injector,
        tag_factory,
        tag_category_factory):
    config_injector({
        'data_dir': str(tmpdir)
    })
    cat1 = tag_category_factory(name='cat1', color='black')
    cat2 = tag_category_factory(name='cat2', color='white')
    session.add_all([cat1, cat2])
    session.flush()
    sug1 = tag_factory(names=['sug1'], category=cat1)
    sug2 = tag_factory(names=['sug2'], category=cat1)
    imp1 = tag_factory(names=['imp1'], category=cat1)
    imp2 = tag_factory(names=['imp2'], category=cat1)
    tag = tag_factory(names=['alias1', 'alias2'], category=cat2)
    tag.post_count = 1
    session.add_all([tag, sug1, sug2, imp1, imp2, cat1, cat2])
    session.flush()
    session.add_all([
        db.TagSuggestion(tag.tag_id, sug1.tag_id),
        db.TagSuggestion(tag.tag_id, sug2.tag_id),
        db.TagImplication(tag.tag_id, imp1.tag_id),
        db.TagImplication(tag.tag_id, imp2.tag_id),
    ])
    session.flush()

    with query_counter:
        tags.export_to_json()
        assert len(query_counter.statements) == 2

    export_path = os.path.join(config.config['data_dir'], 'tags.json')
    assert os.path.exists(export_path)
    with open(export_path, 'r') as handle:
        assert json.loads(handle.read()) == {
            'tags': [
                {
                    'names': ['alias1', 'alias2'],
                    'usages': 1,
                    'category': 'cat2',
                    'suggestions': ['sug1', 'sug2'],
                    'implications': ['imp1', 'imp2'],
                },
                {'names': ['sug1'], 'usages': 0, 'category': 'cat1'},
                {'names': ['sug2'], 'usages': 0, 'category': 'cat1'},
                {'names': ['imp1'], 'usages': 0, 'category': 'cat1'},
                {'names': ['imp2'], 'usages': 0, 'category': 'cat1'},
            ],
            'categories': [
                {'name': 'cat1', 'color': 'black'},
                {'name': 'cat2', 'color': 'white'},
            ]
        }

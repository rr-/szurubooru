"use strict";

const AbstractList = require("./abstract_list.js");
const Note = require("./note.js");

class NoteList extends AbstractList {}

NoteList._itemClass = Note;
NoteList._itemName = "note";

module.exports = NoteList;

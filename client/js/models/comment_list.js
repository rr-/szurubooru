"use strict";

const AbstractList = require("./abstract_list.js");
const Comment = require("./comment.js");

class CommentList extends AbstractList {}

CommentList._itemClass = Comment;
CommentList._itemName = "comment";

module.exports = CommentList;

'use strict';

const views = require('../util/views.js');
const misc = require('../util/misc.js');
const svgNS = 'http://www.w3.org/2000/svg';

class PostNotesOverlayControl {
    constructor(postOverlayNode, post) {
        this._post = post;
        this._postOverlayNode = postOverlayNode;
        this._install();
    }

    _evtMouseEnter(e) {
        const bodyRect = document.body.getBoundingClientRect();
        const svgRect = this._svgNode.getBoundingClientRect();
        const polygonRect = e.target.getBBox();
        this._textNode.querySelector('.wrapper').innerHTML
            = misc.formatMarkdown(e.target.getAttribute('data-text'));
        this._textNode.style.left = (
            svgRect.left + svgRect.width * polygonRect.x) + 'px';
        this._textNode.style.top = (
            svgRect.top + svgRect.height * (
                polygonRect.y + polygonRect.height)) + 'px';
        this._textNode.style.display = 'block';
    }

    _evtMouseLeave(e) {
        const newElement = e.relatedTarget;
        if (newElement === this._svgNode ||
                (!this._svgNode.contains(newElement)
                    && !this._textNode.contains(newElement)
                    && newElement !== this._textNode)) {
            this._textNode.style.display = 'none';
        }
    }

    _install() {
        this._svgNode = document.createElementNS(svgNS, 'svg');
        this._svgNode.classList.add('notes');
        this._svgNode.setAttribute('preserveAspectRatio', 'none');
        this._svgNode.setAttribute('viewBox', '0 0 1 1');
        for (let note of this._post.notes) {
            const polygonNode = document.createElementNS(svgNS, 'polygon');
            polygonNode.setAttribute(
                'vector-effect', 'non-scaling-stroke');
            polygonNode.setAttribute(
                'stroke-alignment', 'inside');
            polygonNode.setAttribute(
                'points', note.polygon.map(point => point.join(',')).join(' '));
            polygonNode.setAttribute('data-text', note.text);
            polygonNode.addEventListener(
                'mouseenter', e => this._evtMouseEnter(e));
            polygonNode.addEventListener(
                'mouseleave', e => this._evtMouseLeave(e));
            this._svgNode.appendChild(polygonNode);
        }
        this._postOverlayNode.appendChild(this._svgNode);

        const wrapperNode = document.createElement('div');
        wrapperNode.classList.add('wrapper');
        this._textNode = document.createElement('div');
        this._textNode.classList.add('note-text');
        this._textNode.appendChild(wrapperNode);
        this._textNode.addEventListener(
            'mouseleave', e => this._evtMouseLeave(e));
        document.body.appendChild(this._textNode);

        views.monitorNodeRemoval(
            this._postOverlayNode, () => { this._uninstall(); });
    }

    _uninstall() {
        this._postOverlayNode.removeChild(this._svgNode);
        document.body.removeChild(this._textNode);
    }
};

module.exports = PostNotesOverlayControl;

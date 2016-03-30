class EventListener {
    constructor() {
        this.listeners = [];
    }

    listen(callback) {
        this.listeners.push(callback);
    }

    unlisten(callback) {
        const index = this.listeners.indexOf(callback);
        if (index !== -1) {
            this.listeners.splice(index, 1);
        }
    }

    fire(data) {
        for (let listener of this.listeners) {
            listener(data);
        }
    }
}

module.exports = EventListener;

class Entity {
    constructor(id) {
        if(id) {
            this.load(id)
        }
    }

    setProperty(name, value) {
        this[name] = value;
    }

    getEntityName() {
        return this.constructor.name;
    }

    load(id) {
        var key = this.getEntityName();
        key = key.substr(0, 1).toLowerCase() + key.substr(1);
        if(typeof data[key][id] != 'undefined') {
            this.hydrate(data[key][id]);
        } else {
            this.load(id);
        }
    }

    hydrate(data) {
        for(let name of Object.keys(data)) {
            this.setProperty(name, data[name]);
        }
    }
}

var data = {};

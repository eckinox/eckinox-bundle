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
        if(typeof data[this.getEntityName().toLowerCase()][id] != 'undefined') {
            this.hydrate(data[this.getEntityName().toLowerCase()][id]);
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

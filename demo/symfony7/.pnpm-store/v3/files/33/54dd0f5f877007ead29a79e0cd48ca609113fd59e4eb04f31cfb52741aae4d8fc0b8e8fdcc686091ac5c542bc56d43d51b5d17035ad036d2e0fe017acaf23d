"use strict";
var __defProp = Object.defineProperty;
var __getOwnPropDesc = Object.getOwnPropertyDescriptor;
var __getOwnPropNames = Object.getOwnPropertyNames;
var __hasOwnProp = Object.prototype.hasOwnProperty;
var __export = (target, all) => {
  for (var name in all)
    __defProp(target, name, { get: all[name], enumerable: true });
};
var __copyProps = (to, from, except, desc) => {
  if (from && typeof from === "object" || typeof from === "function") {
    for (let key of __getOwnPropNames(from))
      if (!__hasOwnProp.call(to, key) && key !== except)
        __defProp(to, key, { get: () => from[key], enumerable: !(desc = __getOwnPropDesc(from, key)) || desc.enumerable });
  }
  return to;
};
var __toCommonJS = (mod) => __copyProps(__defProp({}, "__esModule", { value: true }), mod);

// src/stimulus/helpers/svelte/index.ts
var svelte_exports = {};
__export(svelte_exports, {
  SvelteController: () => render_controller_default,
  registerSvelteControllerComponents: () => registerSvelteControllerComponents
});
module.exports = __toCommonJS(svelte_exports);

// src/stimulus/helpers/svelte/util.ts
var svelteImportedModules = {};
function registerSvelteControllerComponents(modules, controllersDir = "./svelte/controllers") {
  svelteImportedModules = { ...svelteImportedModules, ...modules };
  window.resolveSvelteComponent = (name) => {
    const svelteModule = svelteImportedModules[`${controllersDir}/${name}.svelte`];
    if (typeof svelteModule === "undefined") {
      const possibleValues = Object.keys(svelteImportedModules).map(
        (key) => key.replace(`${controllersDir}/`, "").replace(".svelte", "")
      );
      throw new Error(`Svelte controller "${name}" does not exist. Possible values: ${possibleValues.join(", ")}`);
    }
    return svelteModule;
  };
}

// src/stimulus/helpers/svelte/render_controller.ts
var import_stimulus = require("@hotwired/stimulus");
var import_svelte = require("svelte");
var render_controller_default = class extends import_stimulus.Controller {
  app;
  props;
  intro;
  static values = {
    component: String,
    props: Object,
    intro: Boolean
  };
  connect() {
    this.element.innerHTML = "";
    this.props = this.propsValue ?? void 0;
    this.intro = this.introValue ?? void 0;
    this.dispatchEvent("connect");
    const importedSvelteModule = window.resolveSvelteComponent(this.componentValue);
    const onload = (svelteModule) => {
      const Component = svelteModule.default;
      this._destroyIfExists();
      this.app = (0, import_svelte.mount)(Component, {
        target: this.element,
        props: this.props,
        intro: this.intro
      });
      this.element.root = this.app;
      this.dispatchEvent("mount", {
        component: Component
      });
    };
    if (typeof importedSvelteModule === "function") {
      importedSvelteModule().then(onload);
    } else {
      onload(importedSvelteModule);
    }
  }
  disconnect() {
    this._destroyIfExists();
    this.dispatchEvent("unmount");
  }
  _destroyIfExists() {
    if (this.element.root !== void 0) {
      (0, import_svelte.unmount)(this.element.root);
      delete this.element.root;
    }
  }
  dispatchEvent(name, payload = {}) {
    const detail = {
      componentName: this.componentValue,
      props: this.props,
      intro: this.intro,
      ...payload
    };
    this.dispatch(name, { detail, prefix: "svelte" });
  }
};
// Annotate the CommonJS export names for ESM import in node:
0 && (module.exports = {
  SvelteController,
  registerSvelteControllerComponents
});

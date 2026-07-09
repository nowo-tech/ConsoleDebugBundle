"use strict";
var __create = Object.create;
var __defProp = Object.defineProperty;
var __getOwnPropDesc = Object.getOwnPropertyDescriptor;
var __getOwnPropNames = Object.getOwnPropertyNames;
var __getProtoOf = Object.getPrototypeOf;
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
var __toESM = (mod, isNodeMode, target) => (target = mod != null ? __create(__getProtoOf(mod)) : {}, __copyProps(
  // If the importer is in node compatibility mode or this is not an ESM
  // file that has been converted to a CommonJS file using a Babel-
  // compatible transform (i.e. "__esModule" has not been set), then set
  // "default" to the CommonJS "module.exports" for node compatibility.
  isNodeMode || !mod || !mod.__esModule ? __defProp(target, "default", { value: mod, enumerable: true }) : target,
  mod
));
var __toCommonJS = (mod) => __copyProps(__defProp({}, "__esModule", { value: true }), mod);

// src/stimulus/helpers/index.ts
var helpers_exports = {};
__export(helpers_exports, {
  createLazyController: () => createLazyController,
  registerController: () => registerController,
  registerControllers: () => registerControllers,
  startStimulusApp: () => startStimulusApp
});
module.exports = __toCommonJS(helpers_exports);
var import_stimulus = require("@hotwired/stimulus");
var import_controllers = __toESM(require("virtual:symfony/controllers"), 1);

// src/stimulus/util.ts
var CONTROLLER_FILENAME_REGEX = /^(?:.*?controllers\/|\.?\.\/)?(.+)\.[jt]sx?\b/;
var SNAKE_CONTROLLER_SUFFIX_REGEX = /^(.*)(?:[/_-]controller)$/;
var CAMEL_CONTROLLER_SUFFIX_REGEX = /^(.*)(?:Controller)$/;
function getStimulusControllerId(key, identifierResolutionMethod) {
  if (typeof identifierResolutionMethod === "function") {
    return identifierResolutionMethod(key);
  }
  const [, relativePath] = key.match(CONTROLLER_FILENAME_REGEX) || [];
  if (!relativePath) {
    return null;
  }
  if (identifierResolutionMethod === "snakeCase") {
    const [, identifier] = relativePath.match(SNAKE_CONTROLLER_SUFFIX_REGEX) || [];
    return (identifier ?? relativePath).toLowerCase().replace(/_/g, "-").replace(/\//g, "--");
  } else if (identifierResolutionMethod === "camelCase") {
    const [, identifier] = relativePath.match(CAMEL_CONTROLLER_SUFFIX_REGEX) || [];
    return kebabize(identifier ?? relativePath);
  }
  throw new Error("unknown identifierResolutionMethod valid entries 'snakeCase' or 'camelCase' or custom function");
}
function kebabize(str) {
  return str.split("").map((letter, idx) => {
    if (letter === "/") {
      return "--";
    }
    return letter.toUpperCase() === letter ? `${idx !== 0 && str[idx - 1] !== "/" ? "-" : ""}${letter.toLowerCase()}` : letter;
  }).join("");
}

// src/stimulus/helpers/index.ts
function createLazyController(dynamicImportFactory, exportName = "default") {
  return class extends import_stimulus.Controller {
    constructor(context) {
      context.logDebugActivity = function(functionName) {
        this.application.logDebugActivity(this.identifier + "-lazywrapper", functionName);
      };
      super(context);
      this.__stimulusLazyController = true;
    }
    initialize() {
      if (this.application.controllers.find((controller) => {
        return controller.identifier === this.identifier && controller.__stimulusLazyController;
      })) {
        return;
      }
      dynamicImportFactory().then((controllerModule) => {
        this.application.register(this.identifier, controllerModule[exportName]);
      });
    }
  };
}
function startStimulusApp() {
  const app = import_stimulus.Application.start();
  app.debug = process.env.NODE_ENV === "development";
  for (const controllerInfos of import_controllers.default) {
    if (controllerInfos.fetch === "lazy") {
      app.register(controllerInfos.identifier, createLazyController(controllerInfos.controller));
    } else {
      app.register(controllerInfos.identifier, controllerInfos.controller);
    }
  }
  if (app.debug) {
    console.groupCollapsed("application #startStimulusApp and register controllers from controllers.json");
    console.log(
      "controllers",
      import_controllers.default.map((infos) => infos.identifier)
    );
    console.groupEnd();
  }
  return app;
}
function isLazyLoadedControllerModule(unknownController) {
  if (typeof unknownController === "function") {
    return true;
  }
  return false;
}
function isStimulusControllerConstructor(unknownController) {
  if (unknownController.prototype instanceof import_stimulus.Controller) {
    return true;
  }
  return false;
}
function isStimulusControllerInfosImport(unknownController) {
  if (typeof unknownController === "object" && unknownController[Symbol.toStringTag] === "Module" && unknownController.default) {
    return true;
  }
  return false;
}
function registerControllers(app, modules) {
  const controllersAdded = [];
  if (app.debug) {
    console.groupCollapsed("application #registerControllers");
  }
  Object.entries(modules).forEach(([filePath, unknownController]) => {
    const identifier = getStimulusControllerId(filePath, "snakeCase");
    if (!identifier) {
      throw new Error(`Invalid filePath ${filePath}`);
    }
    if (isLazyLoadedControllerModule(unknownController)) {
      app.register(identifier, createLazyController(unknownController));
      controllersAdded.push(identifier);
    } else if (isStimulusControllerConstructor(unknownController)) {
      app.register(identifier, unknownController);
      controllersAdded.push(identifier);
    } else if (isStimulusControllerInfosImport(unknownController)) {
      registerController(app, unknownController.default);
      controllersAdded.push(unknownController.default.identifier);
    } else {
      throw new Error(
        `unknown Stimulus controller for ${identifier}. if you use import.meta.glob, don't forget to enable the eager option to true`
      );
    }
  });
  if (app.debug) {
    console.groupEnd();
  }
}
function registerController(app, controllerInfos) {
  if (!controllerInfos.enabled) {
    return;
  }
  if (controllerInfos.fetch === "lazy") {
    app.register(controllerInfos.identifier, createLazyController(controllerInfos.controller));
  } else {
    app.register(controllerInfos.identifier, controllerInfos.controller);
  }
  if (app.debug) {
    console.log(`application #registerController ${controllerInfos.identifier}`);
  }
}
// Annotate the CommonJS export names for ESM import in node:
0 && (module.exports = {
  createLazyController,
  registerController,
  registerControllers,
  startStimulusApp
});

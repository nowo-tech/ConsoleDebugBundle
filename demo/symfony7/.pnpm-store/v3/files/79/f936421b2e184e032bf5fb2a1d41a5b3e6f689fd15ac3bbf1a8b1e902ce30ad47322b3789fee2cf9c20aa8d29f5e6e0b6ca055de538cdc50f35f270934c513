// src/stimulus/helpers/index.ts
import { Application, Controller } from "@hotwired/stimulus";
import thirdPartyControllers from "virtual:symfony/controllers";

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
  return class extends Controller {
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
  const app = Application.start();
  app.debug = process.env.NODE_ENV === "development";
  for (const controllerInfos of thirdPartyControllers) {
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
      thirdPartyControllers.map((infos) => infos.identifier)
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
  if (unknownController.prototype instanceof Controller) {
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
export {
  createLazyController,
  registerController,
  registerControllers,
  startStimulusApp
};

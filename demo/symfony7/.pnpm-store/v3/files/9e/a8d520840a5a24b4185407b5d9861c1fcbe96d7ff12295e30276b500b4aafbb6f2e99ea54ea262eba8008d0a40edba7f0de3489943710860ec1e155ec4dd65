// src/entrypoints/index.ts
import path2, { resolve as resolve2, join as join2, relative as relative2, dirname } from "path";
import { existsSync, mkdirSync, readFileSync } from "fs";
import { fileURLToPath } from "url";
import glob from "fast-glob";
import process3 from "process";
import sirv from "sirv";
import colors from "picocolors";

// src/entrypoints/entryPointsHelper.ts
import process2 from "process";

// src/entrypoints/utils.ts
import { loadEnv } from "vite";
import os from "os";
import path from "path";
import { writeFileSync, rmSync, readdirSync } from "fs";
import { resolve, extname, relative } from "path";
import { createHash } from "crypto";

// src/entrypoints/pathMapping.ts
var inputRelPath2outputRelPath = {};
function addIOMapping(relInputPath, relOutputPath) {
  inputRelPath2outputRelPath[relInputPath] = relOutputPath;
}
function getOutputPath(relInputPath) {
  return inputRelPath2outputRelPath[relInputPath];
}
function getInputPath(relOutputPath) {
  return Object.keys(inputRelPath2outputRelPath).find((key) => inputRelPath2outputRelPath[key] === relOutputPath);
}

// src/entrypoints/utils.ts
var isWindows = os.platform() === "win32";
function parseVersionString(str) {
  const [major, minor, patch] = str.split(".").map((nb) => parseInt(nb));
  return [str, major ?? 0, minor ?? 0, patch ?? 0];
}
function slash(p) {
  return p.replace(/\\/g, "/");
}
function trimSlashes(str) {
  return str.replace(/^\/+|\/+$/g, "");
}
function normalizePath(id) {
  return path.posix.normalize(isWindows ? slash(id) : id);
}
function getLegacyName(name) {
  const ext = extname(name);
  const endPos = ext.length !== 0 ? -ext.length : void 0;
  name = name.slice(0, endPos) + "-legacy" + ext;
  return name;
}
function isIpv6(address) {
  return address.family === "IPv6" || // In node >=18.0 <18.4 this was an integer value. This was changed in a minor version.
  // See: https://github.com/laravel/vite-plugin/issues/103
  // eslint-disable-next-line @typescript-eslint/ban-ts-comment
  // @ts-ignore-next-line
  address.family === 6;
}
var writeJson = (filePath, jsonData) => {
  try {
    writeFileSync(filePath, JSON.stringify(jsonData, null, 2));
  } catch (err) {
    throw new Error(`Error writing ${path.basename(filePath)}: ${err.message}`);
  }
};
var INFO_PUBLIC_PATH = "/@vite/info";
var FS_PREFIX = `/@fs/`;
var VALID_ID_PREFIX = `/@id/`;
var CLIENT_PUBLIC_PATH = `/@vite/client`;
var ENV_PUBLIC_PATH = `/@vite/env`;
var importQueryRE = /(\?|&)import=?(?:&|$)/;
var isImportRequest = (url) => importQueryRE.test(url);
var internalPrefixes = [FS_PREFIX, VALID_ID_PREFIX, CLIENT_PUBLIC_PATH, ENV_PUBLIC_PATH];
var InternalPrefixRE = new RegExp(`^(?:${internalPrefixes.join("|")})`);
var isInternalRequest = (url) => InternalPrefixRE.test(url);
var CSS_LANGS_RE = /\.(css|less|sass|scss|styl|stylus|pcss|postcss|sss)(?:$|\?)/;
var cssModuleRE = new RegExp(`\\.module${CSS_LANGS_RE.source}`);
var commonjsProxyRE = /\?commonjs-proxy/;
var isCSSRequest = (request) => CSS_LANGS_RE.test(request);
var polyfillId = "\0vite/legacy-polyfills";
function resolveDevServerUrl(address, config, pluginOptions) {
  if (pluginOptions.originOverride) {
    return pluginOptions.originOverride;
  }
  if (config.server?.origin) {
    return config.server.origin;
  }
  const configHmrProtocol = typeof config.server.hmr === "object" ? config.server.hmr.protocol : null;
  const clientProtocol = configHmrProtocol ? configHmrProtocol === "wss" ? "https" : "http" : null;
  const serverProtocol = config.server.https ? "https" : "http";
  const protocol = clientProtocol ?? serverProtocol;
  const configHmrHost = typeof config.server.hmr === "object" ? config.server.hmr.host : null;
  const configHost = typeof config.server.host === "string" ? config.server.host : null;
  const serverAddress = isIpv6(address) ? `[${address.address}]` : address.address;
  const host = configHmrHost ?? pluginOptions.viteDevServerHostname ?? configHost ?? serverAddress;
  const configHmrClientPort = typeof config.server.hmr === "object" ? config.server.hmr.clientPort : null;
  const port = configHmrClientPort ?? address.port;
  return `${protocol}://${host}:${port}`;
}
var isAddressInfo = (x) => typeof x === "object";
var isCssEntryPoint = (chunk) => {
  if (!chunk.isEntry) {
    return false;
  }
  let isPureCssChunk = true;
  const ids = Object.keys(chunk.modules);
  for (const id of ids) {
    if (!isCSSRequest(id) || cssModuleRE.test(id) || commonjsProxyRE.test(id)) {
      isPureCssChunk = false;
    }
  }
  if (isPureCssChunk) {
    return chunk?.viteMetadata?.importedCss.size === 1;
  }
  return false;
};
var getFileInfos = (chunk, inputRelPath, pluginOptions) => {
  const alg = pluginOptions.sriAlgorithm;
  if (chunk.type === "asset") {
    if (chunk.fileName.endsWith(".css")) {
      return {
        css: [chunk.fileName],
        hash: alg === false ? null : generateHash(chunk.source, alg),
        inputRelPath,
        outputRelPath: chunk.fileName,
        type: "css"
      };
    } else {
      return {
        hash: alg === false ? null : generateHash(chunk.source, alg),
        inputRelPath,
        outputRelPath: chunk.fileName,
        type: "asset"
      };
    }
  } else if (chunk.type === "chunk") {
    const { imports, dynamicImports, viteMetadata, fileName } = chunk;
    return {
      assets: Array.from(viteMetadata?.importedAssets ?? []),
      css: Array.from(viteMetadata?.importedCss ?? []),
      hash: alg === false ? null : generateHash(chunk.code, alg),
      imports,
      inputRelPath,
      js: [fileName],
      outputRelPath: fileName,
      preload: [],
      dynamic: dynamicImports,
      type: "js"
    };
  }
  throw new Error(`Unknown chunktype ${chunk.type} for ${chunk.fileName}`);
};
function generateHash(source, alg) {
  if (alg === false) {
    return null;
  }
  const hash = createHash(alg).update(source).digest().toString("base64");
  return `${alg}-${hash}`;
}
var prepareRollupInputs = (config) => {
  const inputParsed = {};
  const input = config.build.rollupOptions.input ?? config.build.rolldownOptions?.input ?? {};
  for (const [entryName, inputRelPath] of Object.entries(input)) {
    const entryAbsolutePath = normalizePath(resolve(config.root, inputRelPath));
    const extension = extname(inputRelPath);
    const inputType = [".css", ".scss", ".sass", ".less", ".styl", ".stylus", ".postcss"].indexOf(extension) !== -1 ? "css" : "js";
    const entryRelativePath = normalizePath(relative(config.root, entryAbsolutePath));
    inputParsed[entryName] = {
      inputType,
      inputRelPath: entryRelativePath
    };
  }
  return inputParsed;
};
var getInputRelPath = (chunk, options, config) => {
  if (chunk.type === "asset" || !chunk.facadeModuleId) {
    const inputRelPath2 = getInputPath(chunk.fileName);
    if (inputRelPath2) {
      return inputRelPath2;
    }
    return `_${chunk.fileName}`;
  }
  if ([polyfillId].indexOf(chunk.facadeModuleId) !== -1) {
    const baseInputRelPath = chunk.facadeModuleId.replace(/\0/g, "");
    if (chunk.fileName.includes("-legacy")) {
      return `${baseInputRelPath}-legacy`;
    } else {
      return baseInputRelPath;
    }
  }
  let inputRelPath = normalizePath(path.relative(config.root, chunk.facadeModuleId));
  if (chunk.fileName.includes("-legacy") && !chunk.name.includes("-legacy")) {
    inputRelPath = getLegacyName(inputRelPath);
  }
  return inputRelPath.replace(/\0/g, "");
};
function resolveUserExternal(user, id, parentId, isResolved) {
  if (typeof user === "function") {
    return user(id, parentId ?? void 0, isResolved);
  } else if (Array.isArray(user)) {
    return user.some((test) => isExternal(id, test));
  } else {
    return isExternal(id, user);
  }
}
function isExternal(id, test) {
  if (typeof test === "string") {
    return id === test;
  } else {
    return test.test(id);
  }
}
function extractExtraEnvVars(mode, envDir, exposedEnvVars, define) {
  const allVars = loadEnv(mode, envDir, "");
  const availableKeys = Object.keys(allVars).filter((key) => exposedEnvVars.indexOf(key) !== -1);
  const extraDefine = Object.fromEntries(
    availableKeys.map((key) => [`import.meta.env.${key}`, JSON.stringify(allVars[key])])
  );
  return {
    ...extraDefine,
    ...define ?? {}
  };
}
function normalizeConfig(config) {
  const result = JSON.stringify(config, function(k, v) {
    if (k === "plugins" && Array.isArray(v)) {
      return v.filter((v2) => v2.name).map((v2) => v2.name);
    }
    if (typeof v === "function") {
      return void 0;
    }
    return v;
  });
  return result;
}

// src/entrypoints/entryPointsHelper.ts
var getDevEntryPoints = (config, viteDevServerUrl) => {
  const entryPoints = {};
  for (const [entryName, { inputRelPath, inputType }] of Object.entries(prepareRollupInputs(config))) {
    entryPoints[entryName] = {
      [inputType]: [`${viteDevServerUrl}${config.base}${inputRelPath}`]
    };
  }
  return entryPoints;
};
var getFilesMetadatas = (base, generatedFiles) => {
  return Object.fromEntries(
    Object.values(generatedFiles).filter((fileInfos) => fileInfos.hash).map((fileInfos) => [
      `${base}${fileInfos.outputRelPath}`,
      {
        hash: fileInfos.hash
      }
    ])
  );
};
var getBuildEntryPoints = (generatedFiles, viteConfig) => {
  const entryPoints = {};
  let hasLegacyEntryPoint = false;
  const entryFiles = prepareRollupInputs(viteConfig);
  for (const [entryName, entry] of Object.entries(entryFiles)) {
    const outputRelPath = getOutputPath(entry.inputRelPath);
    if (!outputRelPath) {
      console.error("unable to get outputPath", entry.inputRelPath);
      process2.exit(1);
    }
    const fileInfos = generatedFiles[outputRelPath];
    if (!fileInfos) {
      console.error("unable to map generatedFile", entry, outputRelPath, fileInfos);
      process2.exit(1);
    }
    const legacyInputRelPath = getLegacyName(entry.inputRelPath);
    const legacyFileInfos = generatedFiles[getOutputPath(legacyInputRelPath)] ?? null;
    if (legacyFileInfos) {
      hasLegacyEntryPoint = true;
      entryPoints[`${entryName}-legacy`] = resolveBuildEntrypoint(legacyFileInfos, generatedFiles, viteConfig, false);
    }
    entryPoints[entryName] = resolveBuildEntrypoint(
      fileInfos,
      generatedFiles,
      viteConfig,
      hasLegacyEntryPoint ? `${entryName}-legacy` : false
    );
  }
  const polyfills = getOutputPath("vite/legacy-polyfills-legacy");
  if (hasLegacyEntryPoint && polyfills) {
    const fileInfos = generatedFiles[polyfills];
    if (fileInfos) {
      entryPoints["polyfills-legacy"] = resolveBuildEntrypoint(fileInfos, generatedFiles, viteConfig, false);
    }
  }
  const modernPolyfills = getOutputPath("vite/legacy-polyfills");
  if (modernPolyfills) {
    const fileInfos = generatedFiles[modernPolyfills];
    if (fileInfos) {
      entryPoints["polyfills"] = resolveBuildEntrypoint(fileInfos, generatedFiles, viteConfig, false);
    }
  }
  return entryPoints;
};
var resolveBuildEntrypoint = (fileInfos, generatedFiles, config, legacyEntryName, resolvedImportOutputRelPaths = []) => {
  const css = [];
  const js = [];
  const preload = [];
  const dynamic = [];
  resolvedImportOutputRelPaths.push(fileInfos.outputRelPath);
  if (fileInfos.type === "js") {
    for (const importOutputRelPath of fileInfos.imports) {
      if (resolvedImportOutputRelPaths.indexOf(importOutputRelPath) !== -1) {
        continue;
      }
      resolvedImportOutputRelPaths.push(importOutputRelPath);
      const importFileInfos = generatedFiles[importOutputRelPath];
      if (!importFileInfos) {
        const external = config.build.rollupOptions.external ?? config.build.rolldownOptions?.external;
        const isExternal2 = external ? resolveUserExternal(
          external,
          importOutputRelPath,
          // use URL as id since id could not be resolved
          fileInfos.inputRelPath,
          false
        ) : false;
        if (isExternal2) {
          continue;
        }
        throw new Error(`Unable to find ${importOutputRelPath}`);
      }
      const {
        css: importCss,
        dynamic: importDynamic,
        js: importJs,
        preload: importPreload
      } = resolveBuildEntrypoint(importFileInfos, generatedFiles, config, false, resolvedImportOutputRelPaths);
      for (const dependency of importCss) {
        if (css.indexOf(dependency) === -1) {
          css.push(dependency);
        }
      }
      for (const dependency of importJs) {
        if (preload.indexOf(dependency) === -1) {
          preload.push(dependency);
        }
      }
      for (const dependency of importPreload) {
        if (preload.indexOf(dependency) === -1) {
          preload.push(dependency);
        }
      }
      for (const dependency of importDynamic) {
        if (dynamic.indexOf(dependency) === -1) {
          dynamic.push(dependency);
        }
      }
    }
    fileInfos.js.forEach((dependency) => {
      if (js.indexOf(dependency) === -1) {
        js.push(`${config.base}${dependency}`);
      }
    });
    fileInfos.preload.forEach((dependency) => {
      if (preload.indexOf(dependency) === -1) {
        preload.push(`${config.base}${dependency}`);
      }
    });
    fileInfos.dynamic.forEach((dependency) => {
      if (dynamic.indexOf(dependency) === -1) {
        dynamic.push(`${config.base}${dependency}`);
      }
    });
  }
  if (fileInfos.type === "js" || fileInfos.type === "css") {
    fileInfos.css.forEach((dependency) => {
      if (css.indexOf(dependency) === -1) {
        css.push(`${config.base}${dependency}`);
      }
    });
  }
  return { css, dynamic, js, legacy: legacyEntryName, preload };
};

// src/entrypoints/pluginOptions.ts
import { join } from "path";
function resolvePluginEntrypointsOptions(userConfig = {}) {
  if (typeof userConfig.servePublic === "undefined") {
    userConfig.servePublic = "public";
  }
  if (typeof userConfig.sriAlgorithm === "string" && ["sha256", "sha384", "sha512"].indexOf(userConfig.sriAlgorithm.toString()) === -1) {
    userConfig.sriAlgorithm = false;
  }
  return {
    debug: userConfig.debug === true,
    enforcePluginOrderingPosition: userConfig.enforcePluginOrderingPosition === false ? false : true,
    enforceServerOriginAfterListening: userConfig.enforceServerOriginAfterListening === false ? false : true,
    exposedEnvVars: userConfig.exposedEnvVars ?? ["APP_ENV"],
    originOverride: userConfig.originOverride ?? null,
    refresh: userConfig.refresh ?? false,
    servePublic: userConfig.servePublic,
    sriAlgorithm: userConfig.sriAlgorithm ?? false,
    viteDevServerHostname: userConfig.viteDevServerHostname ?? null
  };
}
function resolveOutDir(unknownBase) {
  const baseURL = new URL(unknownBase, import.meta.url);
  const base = baseURL.protocol === "file:" ? unknownBase : baseURL.pathname;
  const publicDirectory = "public";
  return join(publicDirectory, trimSlashes(base));
}
var refreshPaths = ["templates/**/*.twig"];

// src/entrypoints/depreciations.ts
function showDepreciationsWarnings(pluginOptions, logger) {
}

// src/entrypoints/index.ts
var pluginDir = dirname(dirname(fileURLToPath(import.meta.url)));
var pluginVersion;
var bundleVersion;
if (process3.env.VITEST) {
  pluginDir = dirname(pluginDir);
  pluginVersion = ["test"];
  bundleVersion = ["test"];
} else {
  try {
    const packageJson = JSON.parse(readFileSync(join2(pluginDir, "package.json")).toString());
    pluginVersion = parseVersionString(packageJson?.version);
  } catch {
    pluginVersion = [""];
  }
  try {
    const composerJson = JSON.parse(readFileSync("composer.lock").toString());
    bundleVersion = parseVersionString(
      composerJson.packages?.find(
        (composerPackage) => composerPackage.name === "pentatrion/vite-bundle"
      )?.version
    );
  } catch {
    bundleVersion = [""];
  }
}
function symfonyEntrypoints(pluginOptions, logger) {
  let viteConfig;
  let viteDevServerUrl;
  const entryPointsFileName = ".vite/entrypoints.json";
  const generatedFiles = {};
  let outputCount = 0;
  return {
    name: "symfony-entrypoints",
    enforce: "post",
    config(userConfig, { mode }) {
      const root = userConfig.root ? resolve2(userConfig.root) : process3.cwd();
      const envDir = userConfig.envDir ? resolve2(root, userConfig.envDir) : root;
      const extraEnvVars = extractExtraEnvVars(mode, envDir, pluginOptions.exposedEnvVars, userConfig.define);
      const input = userConfig.build?.rollupOptions?.input ?? userConfig.build?.rolldownOptions?.input;
      if (input instanceof Array) {
        logger.error(colors.red("rollupOptions.input must be an Objet like {app: './assets/app.js'}"));
        process3.exit(1);
      }
      const base = userConfig.base ?? "/build/";
      const extraConfig = {
        base,
        publicDir: false,
        build: {
          manifest: true,
          outDir: userConfig.build?.outDir ?? resolveOutDir(base)
        },
        define: extraEnvVars,
        optimizeDeps: {
          //Set to true to force dependency pre-bundling.
          force: true
        },
        server: {
          watch: {
            ignored: userConfig.server?.watch?.ignored ? userConfig.server.watch.ignored : ["**/vendor/**", glob.escapePath(root + "/var") + "/**", glob.escapePath(root + "/public") + "/**"]
          }
        }
      };
      return extraConfig;
    },
    configResolved(config) {
      viteConfig = config;
      if (pluginOptions.enforcePluginOrderingPosition) {
        const pluginPos = viteConfig.plugins.findIndex((plugin) => plugin.name === "symfony-entrypoints");
        const symfonyPlugin = viteConfig.plugins.splice(pluginPos, 1);
        const manifestPos = viteConfig.plugins.findIndex((plugin) => plugin.name === "vite:reporter");
        viteConfig.plugins.splice(manifestPos, 0, symfonyPlugin[0]);
      }
    },
    configureServer(devServer) {
      const { watcher, ws } = devServer;
      const _printUrls = devServer.printUrls;
      devServer.printUrls = () => {
        _printUrls();
        const versions = [];
        if (pluginVersion[0]) {
          versions.push(colors.dim(`vite-plugin-symfony: `) + colors.bold(`v${pluginVersion[0]}`));
        }
        if (bundleVersion[0]) {
          versions.push(colors.dim(`pentatrion/vite-bundle: `) + colors.bold(`${bundleVersion[0]}`));
        }
        const versionStr = versions.length === 0 ? "" : versions.join(colors.dim(", "));
        console.log(`  ${colors.green("\u279C")}  Vite ${colors.yellow("\u26A1\uFE0F")} Symfony: ${versionStr}`);
      };
      devServer.httpServer?.once("listening", () => {
        if (viteConfig.env.DEV && !process3.env.VITEST) {
          showDepreciationsWarnings(pluginOptions, logger);
          const buildDir = resolve2(viteConfig.root, viteConfig.build.outDir);
          const viteDir = resolve2(buildDir, ".vite");
          const address = devServer.httpServer?.address();
          const entryPointsPath = resolve2(viteConfig.root, viteConfig.build.outDir, entryPointsFileName);
          if (!isAddressInfo(address)) {
            logger.error(
              `address is not an object open an issue with your address value to fix the problem : ${address}`
            );
            process3.exit(1);
          }
          if (!existsSync(buildDir)) {
            mkdirSync(buildDir, { recursive: true });
          }
          mkdirSync(viteDir, { recursive: true });
          viteDevServerUrl = resolveDevServerUrl(address, devServer.config, pluginOptions);
          if (pluginOptions.enforceServerOriginAfterListening) {
            viteConfig.server.origin = viteDevServerUrl;
          }
          writeJson(entryPointsPath, {
            base: viteConfig.base,
            entryPoints: getDevEntryPoints(viteConfig, viteDevServerUrl),
            legacy: false,
            metadatas: {},
            version: pluginVersion,
            viteServer: viteDevServerUrl
          });
        }
      });
      if (pluginOptions.refresh !== false) {
        const paths = pluginOptions.refresh === true ? refreshPaths : pluginOptions.refresh;
        for (const path3 of paths) {
          watcher.add(path3);
        }
        watcher.on("change", function(path3) {
          if (path3.endsWith(".twig")) {
            ws.send({
              type: "full-reload"
            });
          }
        });
      }
      devServer.middlewares.use(function symfonyInternalsMiddleware(req, res, next) {
        if (req.url === "/" || req.url === viteConfig.base) {
          res.statusCode = 404;
          res.end(readFileSync(join2(pluginDir, "static/dev-server-404.html")));
          return;
        }
        if (req.url === path2.posix.join(viteConfig.base, INFO_PUBLIC_PATH)) {
          res.statusCode = 200;
          res.setHeader("Content-Type", "application/json");
          res.end(normalizeConfig(viteConfig));
          return;
        }
        return next();
      });
      if (pluginOptions.servePublic !== false) {
        const serve = sirv(pluginOptions.servePublic, {
          dev: true,
          etag: true,
          extensions: [],
          setHeaders(res, pathname) {
            if (/\.[tj]sx?$/.test(pathname)) {
              res.setHeader("Content-Type", "application/javascript");
            }
            res.setHeader("Access-Control-Allow-Origin", "*");
          }
        });
        devServer.middlewares.use(function viteServePublicMiddleware(req, res, next) {
          if (isImportRequest(req.url) || isInternalRequest(req.url)) {
            return next();
          }
          serve(req, res, next);
        });
      }
    },
    async renderChunk(code, chunk) {
      if (!isCssEntryPoint(chunk)) {
        return;
      }
      const cssAssetName = chunk.facadeModuleId ? normalizePath(relative2(viteConfig.root, chunk.facadeModuleId)) : chunk.name;
      chunk.viteMetadata?.importedCss.forEach((cssBuildFilename) => {
        addIOMapping(cssAssetName, cssBuildFilename);
      });
    },
    generateBundle(options, bundle) {
      for (const chunk of Object.values(bundle)) {
        const inputRelPath = getInputRelPath(chunk, options, viteConfig);
        addIOMapping(inputRelPath, chunk.fileName);
        generatedFiles[chunk.fileName] = getFileInfos(chunk, inputRelPath, pluginOptions);
      }
      outputCount++;
      const output = viteConfig.build.rollupOptions?.output ?? viteConfig.build.rolldownOptions?.output;
      const outputLength = Array.isArray(output) ? output.length : 1;
      if (outputCount >= outputLength) {
        const entryPoints = getBuildEntryPoints(generatedFiles, viteConfig);
        this.emitFile({
          fileName: entryPointsFileName,
          source: JSON.stringify(
            {
              base: viteConfig.base,
              entryPoints,
              legacy: typeof entryPoints["polyfills-legacy"] !== "undefined",
              metadatas: getFilesMetadatas(viteConfig.base, generatedFiles),
              version: pluginVersion,
              viteServer: null
            },
            null,
            2
          ),
          type: "asset"
        });
      }
    }
  };
}

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
function generateStimulusId(packageName) {
  if (packageName.startsWith("@")) {
    packageName = packageName.substring(1);
  }
  return packageName.replace(/_/g, "-").replace(/\//g, "--");
}
function kebabize(str) {
  return str.split("").map((letter, idx) => {
    if (letter === "/") {
      return "--";
    }
    return letter.toUpperCase() === letter ? `${idx !== 0 && str[idx - 1] !== "/" ? "-" : ""}${letter.toLowerCase()}` : letter;
  }).join("");
}

// src/stimulus/node/bridge.ts
import { createRequire } from "module";
import { relative as relative3 } from "path";
var virtualSymfonyControllersModuleId = "virtual:symfony/controllers";
function createControllersModule(controllersJsonContent, pluginOptions, logger) {
  const require2 = createRequire(import.meta.url);
  const controllerContents = [];
  let importStatementContents = "";
  let controllerIndex = 0;
  if ("undefined" === typeof controllersJsonContent["controllers"]) {
    throw new Error('Your Stimulus configuration file (assets/controllers.json) lacks a "controllers" key.');
  }
  for (const packageName in controllersJsonContent.controllers) {
    let packageJsonContent = null;
    let packageNameResolved;
    if (packageName === "@symfony/ux-svelte" || packageName === "@symfony/ux-react") {
      packageNameResolved = "vite-plugin-symfony";
    } else {
      packageNameResolved = packageName;
    }
    try {
      packageJsonContent = require2(`${packageNameResolved}/package.json`);
    } catch (error) {
      logger?.error(
        `The file "${packageNameResolved}/package.json" could not be found. Try running "npm install --force".`,
        { error }
      );
    }
    for (const controllerName in controllersJsonContent.controllers[packageName]) {
      const controllerPackageConfig = packageJsonContent?.symfony?.controllers?.[controllerName] || {};
      const controllerUserConfig = controllersJsonContent.controllers[packageName][controllerName];
      if (!controllerUserConfig.enabled) {
        continue;
      }
      const packageMain = controllerUserConfig.module ?? controllerUserConfig.main ?? controllerPackageConfig.module ?? controllerPackageConfig.main ?? packageJsonContent.module ?? packageJsonContent.main;
      const controllerMain = `${packageNameResolved}/${packageMain}`;
      const fetchMode = controllerUserConfig.fetch ?? controllerPackageConfig.fetch ?? pluginOptions.fetchMode;
      let moduleValueContents = ``;
      if (fetchMode === "eager") {
        const controllerNameForVariable = `controller_${controllerIndex++}`;
        importStatementContents += `import ${controllerNameForVariable} from '${controllerMain}';
`;
        moduleValueContents = controllerNameForVariable;
      } else if (fetchMode === "lazy") {
        moduleValueContents = `() => import("${controllerMain}")`;
      } else {
        throw new Error(`Invalid fetch mode "${fetchMode}" in controllers.json. Expected "eager" or "lazy".`);
      }
      let controllerId = generateStimulusId(`${packageName}/${controllerName}`);
      if ("undefined" !== typeof controllerPackageConfig.name) {
        controllerId = controllerPackageConfig.name.replace(/\//g, "--");
      }
      if ("undefined" !== typeof controllerUserConfig.name) {
        controllerId = controllerUserConfig.name.replace(/\//g, "--");
      }
      controllerContents.push(`{
        enabled: true,
        fetch: "${fetchMode}",
        identifier: "${controllerId}",
        controller: ${moduleValueContents}
      }`);
      if (controllerUserConfig.autoimport) {
        for (const autoimport in controllerUserConfig.autoimport) {
          if (controllerUserConfig.autoimport[autoimport]) {
            importStatementContents += "import '" + autoimport + "';\n";
          }
        }
      }
    }
  }
  const moduleContent = `${importStatementContents}
export default [
${controllerContents.join(",\n")}
];
`;
  return moduleContent;
}
var notACommentRE = /^(?<!\/[\\/\\*])\s*/;
var importMetaStimulusFetchRE = /import\.meta\.stimulusFetch\s*=\s*["'](eager|lazy)["']/;
var importMetaStimulusIdentifierRE = /import\.meta\.stimulusIdentifier\s*=\s*["']([a-zA-Z][-_a-zA-Z0-9]*)["']/;
var importMetaStimulusEnabledRE = /import\.meta\.stimulusEnabled\s*=\s*(true|false)/;
var stimulusFetchRE = new RegExp(notACommentRE.source + importMetaStimulusFetchRE.source, "m");
var stimulusIdentifierRE = new RegExp(notACommentRE.source + importMetaStimulusIdentifierRE.source, "m");
var stimulusEnabledRE = new RegExp(notACommentRE.source + importMetaStimulusEnabledRE.source, "m");
function extractStimulusIdentifier(code) {
  return (code.match(stimulusIdentifierRE) || [])[1] ?? null;
}
function parseStimulusRequest(srcCode, moduleId, pluginOptions, viteConfig) {
  let filePath;
  if (moduleId.endsWith("?stimulus")) {
    filePath = moduleId.slice(0, -"?stimulus".length);
  } else {
    filePath = moduleId;
  }
  const fetch = (srcCode.match(stimulusFetchRE) || [])[1] ?? pluginOptions.fetchMode;
  let id = extractStimulusIdentifier(srcCode);
  if (!id) {
    const relativePath = relative3(viteConfig.root, filePath);
    id = getStimulusControllerId(relativePath, pluginOptions.identifierResolutionMethod) ?? generateStimulusId(relativePath);
  }
  const enabled = ((srcCode.match(stimulusEnabledRE) || [])[1] ?? "true") === "false" ? false : true;
  const dstCode = fetch === "eager" ? `
        import Controller from '${filePath}';
        export default {
          enabled: ${enabled},
          fetch: 'eager',
          identifier: '${id}',
          controller: Controller
        }` : `
        export default {
          enabled: ${enabled},
          fetch: 'lazy',
          identifier: '${id}',
          controller: () => import('${filePath}')
        }`;
  return `${dstCode}
if (import.meta.hot) { import.meta.hot.accept(); }`;
}

// src/stimulus/node/index.ts
import { join as join3, relative as relative4, resolve as resolve4 } from "path";

// src/stimulus/node/hmr.ts
var applicationGlobalVarName = "$$stimulusApp$$";
function addBootstrapHmrCode(code, logger) {
  const appRegex = /[^\n]*?\s(\w+)(?:\s*=\s*startStimulusApp\(\))/;
  const appVariable = (code.match(appRegex) || [])[1];
  if (appVariable) {
    logger.info(`stimulus app available globally for HMR with window.${applicationGlobalVarName}`);
    const exportFooter = `window.${applicationGlobalVarName} = ${appVariable}`;
    return `${code}
${exportFooter}`;
  }
  return null;
}
function addControllerHmrCode(code, identifier) {
  const metaHotFooter = `
if (import.meta.hot) {
  import.meta.hot.accept(newModule => {
    if (!window.${applicationGlobalVarName}) {
      console.warn('Stimulus app not available. Are you creating app with startStimulusApp() ?');
      import.meta.hot.invalidate();
    } else {
      if (window.${applicationGlobalVarName}.router.modulesByIdentifier.has('${identifier}') && newModule.default) {
        window.${applicationGlobalVarName}.register('${identifier}', newModule.default);
      } else {
        console.warn('Try to HMR not registered Stimulus controller', '${identifier}', 'full-reload');
        import.meta.hot.invalidate();
      }
    }
  })
}`;
  return `${code}
${metaHotFooter}`;
}

// src/stimulus/node/utils.ts
import { resolve as resolve3, sep } from "path";
function isPathIncluded(basePath, targetPath) {
  const normalizedBasePath = resolve3(basePath);
  const normalizedTargetPath = resolve3(targetPath);
  const basePathWithSep = normalizedBasePath.endsWith(sep) ? normalizedBasePath : normalizedBasePath + sep;
  return normalizedTargetPath.startsWith(basePathWithSep);
}

// src/stimulus/node/index.ts
import { readFile, stat } from "fs/promises";
var stimulusRE = /\?stimulus\b/;
var virtualRE = /^virtual:/;
var isStimulusRequest = (request) => stimulusRE.test(request);
var isVirtualRequest = (request) => virtualRE.test(request);
function symfonyStimulus(pluginOptions, logger) {
  let viteConfig;
  let viteCommand;
  let controllersJsonContent = null;
  let controllersFilePath;
  return {
    name: "symfony-stimulus",
    config(userConfig, { command }) {
      viteCommand = command;
      const extraConfig = {
        optimizeDeps: {
          exclude: [...userConfig?.optimizeDeps?.exclude ?? [], virtualSymfonyControllersModuleId]
        }
      };
      return extraConfig;
    },
    async configResolved(config) {
      viteConfig = config;
      controllersFilePath = resolve4(viteConfig.root, pluginOptions.controllersFilePath);
      try {
        await stat(controllersFilePath);
        controllersJsonContent = JSON.parse((await readFile(controllersFilePath)).toString());
      } catch {
        controllersJsonContent = {
          controllers: {},
          entrypoints: {}
        };
      }
    },
    resolveId(id) {
      if (id === virtualSymfonyControllersModuleId) {
        return id;
      }
    },
    load(id) {
      if (id === virtualSymfonyControllersModuleId) {
        if (controllersJsonContent) {
          return createControllersModule(controllersJsonContent, pluginOptions, logger);
        } else {
          return `export default [];`;
        }
      }
    },
    transform(code, id, options) {
      if (options?.ssr && !process.env.VITEST || id.includes("node_modules") || isVirtualRequest(id)) {
        return null;
      }
      if (isStimulusRequest(id)) {
        return parseStimulusRequest(code, id, pluginOptions, viteConfig);
      }
      if (viteCommand === "serve" && pluginOptions.hmr) {
        if (id.endsWith("bootstrap.js") || id.endsWith("bootstrap.ts")) {
          return addBootstrapHmrCode(code, logger);
        }
        const isInsideControllerDir = isPathIncluded(join3(viteConfig.root, pluginOptions.controllersDir), id);
        if (!isInsideControllerDir) {
          return null;
        }
        const relativePath = relative4(viteConfig.root, id);
        const identifier = extractStimulusIdentifier(code) ?? getStimulusControllerId(relativePath, pluginOptions.identifierResolutionMethod);
        if (identifier) {
          return addControllerHmrCode(code, identifier);
        }
      }
      return null;
    },
    configureServer(devServer) {
      const { watcher } = devServer;
      watcher.on("change", (path3) => {
        if (path3 === controllersFilePath) {
          logger.info("\u2728 controllers.json updated, we restart server.");
          devServer.restart();
        }
      });
    }
  };
}

// src/logger.ts
import readline from "readline";
import colors2 from "picocolors";
var LogLevels = {
  silent: 0,
  error: 1,
  warn: 2,
  info: 3
};
var lastType;
var lastMsg;
var sameCount = 0;
function clearScreen() {
  const repeatCount = process.stdout.rows - 2;
  const blank = repeatCount > 0 ? "\n".repeat(repeatCount) : "";
  console.log(blank);
  readline.cursorTo(process.stdout, 0, 0);
  readline.clearScreenDown(process.stdout);
}
function createLogger(level = "info", options = {}) {
  if (options.customLogger) {
    return options.customLogger;
  }
  const timeFormatter = new Intl.DateTimeFormat(void 0, {
    hour: "numeric",
    minute: "numeric",
    second: "numeric"
  });
  const loggedErrors = /* @__PURE__ */ new WeakSet();
  const { prefix = "[vite]", allowClearScreen = true } = options;
  const thresh = LogLevels[level];
  const canClearScreen = allowClearScreen && process.stdout.isTTY && !process.env.CI;
  const clear = canClearScreen ? clearScreen : () => {
  };
  function output(type, msg, options2 = {}) {
    if (thresh >= LogLevels[type]) {
      const method = type === "info" ? "log" : type;
      const format = () => {
        const tag = type === "info" ? colors2.cyan(colors2.bold(prefix)) : type === "warn" ? colors2.yellow(colors2.bold(prefix)) : colors2.red(colors2.bold(prefix));
        if (options2.timestamp) {
          return `${colors2.dim(timeFormatter.format(/* @__PURE__ */ new Date()))} ${tag} ${msg}`;
        } else {
          return `${tag} ${msg}`;
        }
      };
      if (options2.error) {
        loggedErrors.add(options2.error);
      }
      if (canClearScreen) {
        if (type === lastType && msg === lastMsg) {
          sameCount++;
          clear();
          console[method](format(), colors2.yellow(`(x${sameCount + 1})`));
        } else {
          sameCount = 0;
          lastMsg = msg;
          lastType = type;
          if (options2.clear) {
            clear();
          }
          console[method](format());
        }
      } else {
        console[method](format());
      }
    }
  }
  const warnedMessages = /* @__PURE__ */ new Set();
  const logger = {
    hasWarned: false,
    info(msg, opts) {
      output("info", msg, opts);
    },
    warn(msg, opts) {
      logger.hasWarned = true;
      output("warn", msg, opts);
    },
    warnOnce(msg, opts) {
      if (warnedMessages.has(msg)) return;
      logger.hasWarned = true;
      output("warn", msg, opts);
      warnedMessages.add(msg);
    },
    error(msg, opts) {
      logger.hasWarned = true;
      output("error", msg, opts);
    },
    clearScreen(type) {
      if (thresh >= LogLevels[type]) {
        clear();
      }
    },
    hasErrorLogged(error) {
      return loggedErrors.has(error);
    }
  };
  return logger;
}

// src/stimulus/pluginOptions.ts
function resolvePluginStimulusOptions(userConfig) {
  let config;
  if (userConfig === true) {
    config = {
      controllersDir: "./assets/controllers",
      controllersFilePath: "./assets/controllers.json",
      hmr: true,
      fetchMode: "eager",
      identifierResolutionMethod: "snakeCase"
    };
  } else if (typeof userConfig === "string") {
    config = {
      controllersDir: "./assets/controllers",
      controllersFilePath: userConfig,
      hmr: true,
      fetchMode: "eager",
      identifierResolutionMethod: "snakeCase"
    };
  } else if (typeof userConfig === "object") {
    config = {
      controllersDir: userConfig.controllersDir ?? "./assets/controllers",
      controllersFilePath: userConfig.controllersFilePath ?? "./assets/controllers.json",
      hmr: userConfig.hmr !== false ? true : false,
      fetchMode: userConfig.fetchMode === "lazy" ? "lazy" : "eager",
      identifierResolutionMethod: userConfig.identifierResolutionMethod ?? "snakeCase"
    };
  } else {
    config = false;
  }
  return config;
}

// src/index.ts
function symfony(userPluginOptions = {}) {
  const { stimulus: userStimulusOptions, ...userEntrypointsOptions } = userPluginOptions;
  const entrypointsOptions = resolvePluginEntrypointsOptions(userEntrypointsOptions);
  const stimulusOptions = resolvePluginStimulusOptions(userStimulusOptions);
  const plugins = [
    symfonyEntrypoints(
      entrypointsOptions,
      createLogger("info", { prefix: "[symfony:entrypoints]", allowClearScreen: true })
    )
  ];
  if (typeof stimulusOptions === "object") {
    plugins.push(
      symfonyStimulus(stimulusOptions, createLogger("info", { prefix: "[symfony:stimulus]", allowClearScreen: true }))
    );
  }
  return plugins;
}
export {
  symfony as default
};

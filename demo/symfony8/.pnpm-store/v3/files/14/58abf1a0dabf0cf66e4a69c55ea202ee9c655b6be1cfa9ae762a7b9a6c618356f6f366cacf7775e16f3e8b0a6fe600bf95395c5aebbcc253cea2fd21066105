import * as _hotwired_stimulus_dist_types_core_value_properties from '@hotwired/stimulus/dist/types/core/value_properties';
import * as _hotwired_stimulus_dist_types_core_outlet_properties from '@hotwired/stimulus/dist/types/core/outlet_properties';
import * as _hotwired_stimulus_dist_types_core_data_map from '@hotwired/stimulus/dist/types/core/data_map';
import * as _hotwired_stimulus_dist_types_core_class_map from '@hotwired/stimulus/dist/types/core/class_map';
import * as _hotwired_stimulus_dist_types_core_outlet_set from '@hotwired/stimulus/dist/types/core/outlet_set';
import * as _hotwired_stimulus_dist_types_core_target_set from '@hotwired/stimulus/dist/types/core/target_set';
import * as _hotwired_stimulus_dist_types_core_scope from '@hotwired/stimulus/dist/types/core/scope';
import { Context, Application, ControllerConstructor } from '@hotwired/stimulus';

declare module "@hotwired/stimulus" {
    interface Controller {
        __stimulusLazyController: boolean;
    }
}
declare function createLazyController(dynamicImportFactory: LazyLoadedStimulusControllerModule, exportName?: string): {
    new (context: Context): {
        initialize(): void;
        readonly context: Context;
        readonly application: Application;
        readonly scope: _hotwired_stimulus_dist_types_core_scope.Scope;
        readonly element: Element;
        readonly identifier: string;
        readonly targets: _hotwired_stimulus_dist_types_core_target_set.TargetSet;
        readonly outlets: _hotwired_stimulus_dist_types_core_outlet_set.OutletSet;
        readonly classes: _hotwired_stimulus_dist_types_core_class_map.ClassMap;
        readonly data: _hotwired_stimulus_dist_types_core_data_map.DataMap;
        connect(): void;
        disconnect(): void;
        dispatch(eventName: string, { target, detail, prefix, bubbles, cancelable, }?: Partial<{
            target: Element | Window | Document;
            detail: Object;
            prefix: string;
            bubbles: boolean;
            cancelable: boolean;
        }> | undefined): CustomEvent<Object>;
        __stimulusLazyController: boolean;
    };
    blessings: (typeof _hotwired_stimulus_dist_types_core_outlet_properties.OutletPropertiesBlessing)[];
    targets: string[];
    outlets: string[];
    values: _hotwired_stimulus_dist_types_core_value_properties.ValueDefinitionMap;
    readonly shouldLoad: boolean;
    afterLoad(_identifier: string, _application: Application): void;
};
declare function startStimulusApp(): Application;
type Module = StimulusControllerInfosImport | LazyLoadedStimulusControllerModule | ControllerConstructor;
type Modules = Record<string, Module>;
declare function registerControllers(app: Application, modules: Modules): void;
declare function registerController(app: Application, controllerInfos: StimulusControllerInfos): void;

export { createLazyController, registerController, registerControllers, startStimulusApp };

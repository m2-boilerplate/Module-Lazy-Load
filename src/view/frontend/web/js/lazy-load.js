define(['m2bpVanillaLazyLoad'], function (LazyLoad) {
    var instance = null;

   return function (config) {
       if (!instance) {
           instance = new LazyLoad(config);
       } else {
           instance.update();
       }
   }
});
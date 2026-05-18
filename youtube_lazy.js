/*Script leve que adia o carregamento da iframe_api do YouTube até o usuário clicar no play. Evita requisições desnecessárias no carregamento da página para widgets de vídeo do Elementor com overlay de imagem personalizado.*/

(function(){

    var ytLoaded = false;

    // Bloqueia a criacao do script da API do YouTube

    var _createElement = document.createElement.bind(document);

    document.createElement = function(tagName) {

        var el = _createElement(tagName);

        if (typeof tagName === "string" && tagName.toLowerCase() === "script") {

            var descriptor = Object.getOwnPropertyDescriptor(HTMLScriptElement.prototype, "src");

            Object.defineProperty(el, "src", {

                configurable: true,

                get: function() {

                    return descriptor.get.call(this);

                },

                set: function(val) {

                    if (!ytLoaded && val && val.includes("youtube.com/iframe_api")) {

                        return; // bloqueia silenciosamente

                    }

                    descriptor.set.call(this, val);

                }

            });

        }

        return el;

    };

    // Ao clicar no play, restaura tudo e libera o YouTube carregar

    document.addEventListener("click", function handler(e) {

        if (e.target.closest(".elementor-custom-embed-image-overlay, .elementor-custom-embed-play")) {

            ytLoaded = true;

            document.createElement = _createElement; // restaura original

            document.removeEventListener("click", handler, true);

        }

    }, true);

})();

onmessage = function(event) {
    try{
        var block = event.data.replace(/^.*{/m, '{');
        var code  = new Function(block);
        if(code() == true){
            postMessage("uhai42ludkxRdvjmfb");
        }
    }catch(e){
        throw e;
    }
};

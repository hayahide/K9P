var TPL=function(param){

        if(param==undefined) return;

        var param=param||{};
        this.cache={};
        this.tagStripe={};
        this.exp=/<<([\w]+)>>/g;
        this.addGetter(param);
}

TPL.prototype.replaceLanguage=function(languages,tmpl){

        var self=this;
        return tmpl.replace(/##(.+?)##/g,function(match,match_str){

                return languages[!!self.tagStripe[match_str]?self.tagStripe[match_str]:match_str.stripTags()];
        });
}

//■テンプレートから番号取得
TPL.prototype.getLocalizeNum=function(tmpl){

        var r;
        var match=[];
        var reg=/<<([\d]+)>>/g;
        while((r=reg.exec(tmpl))!==null) match.push(r[1]);
        return match;
}

TPL.prototype.getLocalizeStringsByTpl=function(tmpl){

        var r;
        var match=[];
        var reg=/##(.+?)##/g;
        while((r=reg.exec(tmpl))!==null){

                this.tagStripe[r[1]]=r[1].stripTags();
                match.push(this.tagStripe[r[1]]);
        }
        return match;
}

//■Localize変換
TPL.prototype.getLocalizeString=function(keys,success){

        if(1>keys.length){

                success({});
                return;
        }

        getLanguageString(function(res){

                success(res);

        },function(res){


        },keys);
}

TPL.prototype.addGetter=function(param){

        if($.isArray(param)){

                var self=this;
                param.forEach(function(k,v){

                        return self.addGetter(k);
                });
                return;
        }

		var self=this;
		if(!!self.cache[param]) return;
        //if(!!this.__proto__["get_"+param]) return;

        var self=this;
        this.__defineGetter__("get_"+param,function(param){

                if(!!self.cache[param]) return self.cache[param];
                var html=$("script[id=tpl_"+param+"]").html();
                self.cache[param]=html;
                return html;

        }.bind(this,param));
}

TPL.prototype.replaceTPL=function(str,change_code){

	var res=str.replace(this.exp,function(index,match){

		if(!change_code[match]) return "<<"+match+">>";
		return (change_code[match]+"").replace(/\\/g,'');
	});

	return res;
}

//■保存済みか調査
TPL.checkKey=function(cache,keys,saves){

        saves=(saves==undefined)?[]:saves;
        if(1>keys.length) return saves;

        var fn=arguments.callee;
        var key=keys.shift();
        if(!cache[key]) saves.push(key);
        return fn.call(TPL,cache,keys,saves);
}

//■テンプレートを使い回す
TPL.getInstance=function(param){

        if(!!this["instance"] && this.instance instanceof this){

                //■未設定の場合
                if(!$.isArray(param)) param=[param];
                if(1>param.length) return this.instance;
                var keys=this.checkKey(this.instance.cache,param.slice(0));
                if(keys.length>0) this.instance.addGetter(keys);
                return this.instance;
        }

        this.instance=new this(param);
        return this.instance;
}

TPL.prototype.clear=function(tpl){

        return tpl.replace(this.exp,"");
}

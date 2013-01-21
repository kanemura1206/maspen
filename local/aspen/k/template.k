/*
 * template.k : template script for output initializr javascript
 */

import("Syntax.CStyleWhile");
import("JavaScript.Array");
import("Syntax.JavaStyleClass");
//import("MiniKonoha.NameSpace");
import("Type.File");
import("JavaScript.String");

@Public int Array_indexOf(String[] sa, String val) {
	int ret = -1;
	int i = 0;
	while(i < sa.getlength()) {
		if(sa[i] == val) {
			return i;
		}
		i = i + 1;
	}
	return ret;
}

//const BASE_PATH="/var/www/html/maspen/local/aspen/static";

class Template {
	File file;
	String[] params;
	String[] paramkeys;

	Template(File f) {
		this.file = f;
		this.params = [];
		this.paramkeys = [];
		this.file;// to avoid $Indent bug
	}

	@Public @Static Template getTemplate(String templateName) {
		String BASE_PATH="/var/www/html/maspen/local/aspen/static";
		return new Template(new File(BASE_PATH + "/" + templateName, "r"));
	}

	@Public void set(String key, String val) {
		if(Array_indexOf(this.paramkeys, key) == -1) {
			this.paramkeys.add(key);
			this.params.add(val);
		}
		else {
			this.params.set(Array_indexOf(this.paramkeys, key), val);
		}
	}

	String applyParams(String str) {
		String ret = str;
		int i = 0;
		while(i < this.paramkeys.getlength()) {
			ret = ret.replace("%" + this.paramkeys[i] + "%", this.params[i]);
			i = i + 1;
		}
		return ret;
	}

	@Public String render() {
		String ret = "";
		String buf = this.file.readLine();
		while(buf.isNotNull()) {
			ret = ret + buf + "\n";
			buf = this.file.readLine();
		}
		return applyParams(ret);
	}
}

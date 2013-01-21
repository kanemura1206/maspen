import("Syntax.CStyleWhile");
import("JavaScript.Array");
import("JavaScript.Regexp");
import("Syntax.JavaStyleClass");

load("./decodeURI.k");

class CGI {
	String[] querylist;

	CGI(String query) {
		this.querylist = query.split(/&/);
		this.querylist; // to avoid $Indent bug
	}

	@Public String getParam(String key) {
		int i = 0;
		while(i < querylist.getlength()) {
			String[] k_v = querylist.get(i).split(/=/);
			if(decodeURI(k_v[0]) == key) {
				return decodeURI(k_v[1]);
			}
			i = i + 1;
		}
		return "";
	}
}

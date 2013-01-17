import("Syntax.CStyleWhile");
import("JavaScript.String");
import("JavaScript.Array");
import("Java.Class");

load("./decodeURI.k");

class CGI {
	String[] querylist;

	CGI(String query) {
		this.querylist = query.split(/&/);
	}

	String getParam(String key) {
		int i = 0;
		while(i < querylist.getSize()) {
			String[] k_v = querylist[i].split(/=/);
			if(k_v[0].decodeURI() == key) {
				return k_v[1].decodeURI();
			}
			i = i + 1;
		}
		return null;
	}
}

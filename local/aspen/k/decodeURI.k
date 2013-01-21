/*
 * decode URI
 * encoded URI should be followed RFC3986.
 * http://www.ietf.org/rfc/rfc3986.txt
 * character encode is fixed to UTF-8.
 * kindofbrown(c)2009
 * update by myoan 28/Jul/2012 fix to follow new konoha.
 * update by myoan 22/Oct/2012 fix to follow new konoha.
 */

import("Syntax.CStyleWhile");
import("Type.Bytes");
import("JavaScript.Regexp");
//import("js4.string");

//import("cstyle", "while");
//import("cstyle", "break");
//import("konoha.bytes");
//import("js4.regexp");

int power(int base, int num) {
	int i = 0;
	int ret = 1;
	while(i < num) {
		ret = ret * base;
		i = i + 1;
	}
	return ret;
}

int strHexToDec(String hex) {
	int ret = 0;
	String tmp = hex.replace(/0x/, "");
	int i = 0;
	int size = tmp.getlength();
	while(i < tmp.getlength()) {
		size = size - 1;
		String c = tmp.get(i);
		if (c.search(/[a-f]|[A-F]|[0-9]/) < 0) {
			return -1;
		}
		else if (c.search(/[0-9]/) >= 0) {
			int ii = c.toint();
			int ii = ii * power(16, size);
			ret = ret + ii;
		}
		else if (c.search(/[a-f]|[A-F]/) >= 0) {
			if (c == "a" || c == "A") {
				int ii = 10 * power(16, size);
				ret = ret + ii;
			}
			else if (c == "b" || c == "B") {
				int ii = 11 * power(16, size);
				ret = ret + ii;
			}
			else if (c == "c" || c == "C") {
				int ii = 12 * power(16, size);
				ret = ret + ii;
			}
			else if (c == "d" || c == "D") {
				int ii = 13 * power(16, size);
				ret = ret + ii;
			}
			else if (c == "e" || c == "E") {
				int ii = 14 * power(16, size);
				ret = ret + ii;
			}
			else if (c == "f" || c == "F") {
				int ii = 15 * power(16, size);
				ret = ret + ii;
			}
		}
		i = i + 1;
	}
	//System.p(ret);
	return ret;
}

int ampersand(int a, int b) {
	return a % b;
}

int lshift(int a, int b) {
	int i = 0;
	while(i < b) {
		a = a * 2;
		i = i + 1;
	}
	return a;
}

String Bytes.toString() {
	Bytes buf = this;
	int i = 0;
	String ret = "";
	while(i < buf.getlength()) {
		int c = buf.get(i);
		//System.p(buf.get(i));
		ret = ret + String.fromCharCode(buf.get(i));
		//System.p(ret);
		i = i + 1;
	}
	return ret;
}

@Public String decodeURI(String str) {
	int i = 0;
	int setcnt;
	int len = str.getlength();
	Bytes buf = new Bytes(str.getlength());
	int decoded = 0;
	int decode_flag = 0;
	boolean lf_flag = false;
	while(i < len) {
		String c = str.get(i);
		//System.p(i);
		//System.p(c);
		if (c == "+") {
			buf.set(setcnt, " ".charCodeAt(0));
			setcnt = setcnt + 1;
		} else if (c == "%") {
			String hex = "0x";
			int j = 0;
			while(j < 2) {
				i = i + 1;
				c = str.get(i);
				//System.p("i=" + i + ",c=" + c);
				if (c.search(/[a-f]|[A-F]|[0-9]/) >= 0) {
					hex = hex + c;
				} else {
					i = i - 1;
					break;
				}
				j = j + 1;
			}
			//System.p(hex);
			int u = strHexToDec(hex);
			//// recognize LF, CR, CR + LF
			if (u == 0x0D && lf_flag == false) {
				lf_flag = true;
			} else if (lf_flag == true && u == 0x0A) {
				// CR + LF
				buf.set(setcnt, "\n".charCodeAt(0));
				setcnt = setcnt + 1;
				lf_flag = false;
			} else {
				// decoding phase
				buf.set(setcnt, u);
				setcnt = setcnt + 1;
			}
		//} else if (c.search(/[a-z]|[A-Z]|[0-9]|\*|\-|\.|_/) >= 0) {
		//	buf.set(setcnt, c.toBytes().get(0));
		//	setcnt = setcnt + 1;
		} else {
			buf.set(setcnt, c.toBytes().get(0));
			setcnt = setcnt + 1;
		}
		i = i + 1;
	}
	return new String(buf);
}

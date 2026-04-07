LOGIN=xlagodm00

all: $(LOGIN).zip

$(LOGIN).zip: Dockerfile int tester
	zip -r $@ $^

clean:
	rm -f $(LOGIN).zip

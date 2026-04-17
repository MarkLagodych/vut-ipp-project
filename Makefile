LOGIN=xlagodm00

.PHONY: all clean

all: $(LOGIN).zip

$(LOGIN).zip: Dockerfile int tester
	zip $@ $(shell git ls-files $^)

clean:
	rm -f $(LOGIN).zip

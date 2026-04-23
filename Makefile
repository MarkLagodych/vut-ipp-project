LOGIN=xlagodm00

.PHONY: all clean

all: $(LOGIN).zip

$(LOGIN).zip: Dockerfile dokumentace.md diagram.png int tester
	zip $@ $(shell git ls-files $^)

clean:
	rm -f $(LOGIN).zip
